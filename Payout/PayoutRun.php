<?php
#App\GP247\Plugins\MultiVendor\Payout\PayoutRun.php
namespace App\GP247\Plugins\MultiVendor\Payout;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use App\GP247\Plugins\MultiVendor\Commission\CommissionPolicy;
use App\GP247\Plugins\MultiVendor\Events\PaidVendor;
use App\GP247\Plugins\MultiVendor\Events\PayingVendor;
use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier;
use GP247\Shop\Models\ShopOrder;

/**
 * The payout run and the "paid" step of the vendor payout ledger.
 *
 * Moved out of the root payment screen (VendorPaymentManager) so the screen and
 * any other caller — a seeding command, a test — go through one implementation.
 * The algorithm is the one the screen ran: validate the processing date, gather
 * the completed orders of the window per store × currency, apply the commission
 * split, book the rows, record which orders each row paid and net the pending
 * adjustments. Authorization stays with the caller (the screen checks RBAC).
 *
 * Dates come from now() (Carbon), never date(): one clock for the whole plugin,
 * so a run can be replayed on a past day (RISK-TECH-mv-native-clock-blocks-simulation).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-root-admin-livewire
 */
final class PayoutRun
{
    /** Refusal: no processing date. */
    public const ERR_DATE = 'multi_vendor.vendor_payment_date_validate';

    /** Refusal: the date is not after the latest processed date. */
    public const ERR_DATE_EXISTS = 'multi_vendor.vendor_payment_date_exist';

    /**
     * Book one payout period up to $processDate (inclusive).
     *
     * @param string $processDate Y-m-d; must be before today and after the last processed date.
     * @return array{error: ?string, rows: array<int, array<string, mixed>>} error = language key of the refusal, or null.
     */
    public static function process(string $processDate): array
    {
        $startProcess = $processDate;
        if (!$startProcess || $startProcess >= now()->toDateString()) {
            return ['error' => self::ERR_DATE, 'rows' => []];
        }
        $checkDateProcess = AdminMoneyProcess::selectRaw('max(date_process) as date_process')->first()->date_process;
        if ($checkDateProcess && $startProcess <= $checkDateProcess) {
            return ['error' => self::ERR_DATE_EXISTS, 'rows' => []];
        }
        // WHY not the root store: its orders are the marketplace's own revenue, not
        // money owed to a vendor (RISK-BIZ-mv-root-orders-in-vendor-payout).
        $dataPay = (new ShopOrder)
            ->selectRaw('sum(total) as total_sum, count(*) as order_count, currency, store_id')
            ->where('status', 5)
            ->where('store_id', '<>', GP247_STORE_ID_ROOT)
            ->where('finish_date', '<=', $startProcess);
        if ($checkDateProcess) {
            $dataPay = $dataPay->where('finish_date', '>', $checkDateProcess);
        }
        // S3-1: the same order set, per order, so the period can record what it paid.
        $ordersOfPeriod = (new ShopOrder)
            ->select('id', 'store_id', 'currency', 'total')
            ->where('status', 5)
            ->where('store_id', '<>', GP247_STORE_ID_ROOT)
            ->where('finish_date', '<=', $startProcess)
            ->when($checkDateProcess, fn ($q) => $q->where('finish_date', '>', $checkDateProcess))
            ->get()
            ->groupBy(fn ($o) => $o->store_id.'|'.$o->currency);
        $dataPay = $dataPay->groupBy('store_id', 'currency')
            ->get()
            ->toArray();
        $dataInsert = [];
        foreach ($dataPay as $dataPayRaw) {
            $dataPayRaw['id'] = gp247_uuid();
            $dataPayRaw['content'] = 'Payment from '.$checkDateProcess.' to '.$startProcess;
            $dataPayRaw['date_process'] = $startProcess;
            // S1-1: the rate is per store (override row) with the marketplace rate as
            // fallback; the ledger keeps the vendor share actually applied to this period.
            $rate = CommissionPolicy::rateFor((string) $dataPayRaw['store_id']);
            $dataPayRaw['commission_rate'] = CommissionPolicy::vendorShare($rate);
            $dataPayRaw['amount'] = CommissionPolicy::amount((float) $dataPayRaw['total_sum'], $rate, (string) $dataPayRaw['currency']);
            // S1-5: snapshot where the vendor wants to be paid, as it stood when the period was built.
            $account = Payout::accountSnapshot((string) $dataPayRaw['store_id']);
            $dataPayRaw['payout_method'] = $account['method'] !== '' ? $account['method'] : null;
            $dataPayRaw['payout_account'] = $account['masked'] !== '' ? $account['masked'] : null;
            $dataPayRaw['created_at'] = now()->toDateTimeString();
            $dataPayRaw['comment'] = '';
            // S3-2 (Q1-B): money of an unverified store is booked but held (`pending`)
            // while the marketplace requires KYC; root releases it once verified.
            $dataPayRaw['status'] = Kyc::payoutStatusFor((string) $dataPayRaw['store_id']);
            if ($dataPayRaw['status'] === 'pending') {
                $dataPayRaw['comment'] = 'KYC pending';
            }
            $dataInsert[] = $dataPayRaw;
        }

        PayingVendor::dispatch($dataInsert);

        if ($dataInsert) {
            AdminMoneyProcess::insert($dataInsert);
            // S3-1: remember which orders each period row paid, then absorb pending
            // clawback/refund adjustments of the same store × currency into the new row.
            foreach ($dataInsert as $i => $row) {
                $key = $row['store_id'].'|'.$row['currency'];
                Payout::recordPeriodOrders((string) $row['id'], (string) $row['store_id'], (string) $row['currency'], (int) $row['commission_rate'], $ordersOfPeriod->get($key, collect()));
                $net = Payout::netPending((string) $row['store_id'], (string) $row['currency'], (string) $row['id']);
                if ($net != 0.0) {
                    $dataInsert[$i]['amount'] = round((float) $row['amount'] + $net, CommissionPolicy::precision((string) $row['currency']));
                    AdminMoneyProcess::where('id', $row['id'])->update([
                        'amount' => $dataInsert[$i]['amount'],
                        'comment' => trim('Adjustments netted: '.$net),
                    ]);
                }
            }
        }

        PaidVendor::dispatch($dataInsert);

        return ['error' => null, 'rows' => $dataInsert];
    }

    /**
     * Save a ledger row; moving it to `done` stamps who paid and when, and tells the vendor once.
     *
     * @param AdminMoneyProcess   $payment Row to update.
     * @param array<string,mixed> $data    Cleaned column values (the edit form, or just a status).
     * @param mixed               $adminId Admin recorded in paid_by (0 when unknown).
     */
    public static function update(AdminMoneyProcess $payment, array $data, $adminId): void
    {
        $wasDone = (string) $payment->status === 'done';
        // S1-5: the admin who marks the row done is recorded once.
        if (!$wasDone && (string) ($data['status'] ?? '') === 'done') {
            $data['paid_by'] = (string) $adminId;
            if (empty($data['date_pay'] ?? null) && empty($payment->date_pay)) {
                $data['date_pay'] = now()->toDateString();
            }
        }
        $payment->update($data);
        // E4 (S1-2): the admin marking the row `done` is the "paid" moment (PaidVendor
        // fires at period creation, not here) — tell the vendor once.
        if (!$wasDone && (string) $payment->status === 'done') {
            VendorNotifier::payoutDone($payment->fresh() ?? $payment);
        }
    }

    /**
     * Mark one ledger row paid (the "done" step without the rest of the edit form).
     *
     * @param AdminMoneyProcess $payment   Row to settle.
     * @param mixed             $adminId   Admin recorded in paid_by.
     * @param string            $reference Bank/transfer reference, optional.
     */
    public static function markDone(AdminMoneyProcess $payment, $adminId, string $reference = ''): void
    {
        $data = ['status' => 'done'];
        if ($reference !== '') {
            $data['payout_reference'] = $reference;
        }
        self::update($payment, $data, $adminId);
    }
}
