<?php

namespace App\GP247\Plugins\MultiVendor\Payout;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use GP247\Shop\Payment\Contracts\PurposeResolver;
use GP247\Shop\Payment\Contracts\ValidatesRequest;
use GP247\Shop\Payment\Models\PaymentMovement;
use GP247\Shop\Payment\Models\PaymentRequest;
use GP247\Shop\Payment\PaymentCurrency;

/**
 * Purpose `vendor.payout` of the core's payment requests: pay one period payout row of
 * a vendor. The admin holding the money-out permission records the transfer on the
 * request; once the request is fully paid the row is marked done through the payout
 * run, exactly like the "done" step on the payout screen (vendor notified once).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-payout-payment-request
 */
class VendorPayoutPurpose implements PurposeResolver, ValidatesRequest
{
    public const KEY = 'vendor.payout';

    public const SUBJECT_TYPE = 'vendor_payout';

    /** @var bool|null Memoised: the owner's table exists. */
    private static ?bool $tableReady = null;

    /**
     * Whether the tables this purpose writes to exist (package/plugin install really ran).
     *
     * @return bool
     */
    public static function available(): bool
    {
        if (self::$tableReady === null) {
            try {
                $model = new \App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess();
                self::$tableReady = \Illuminate\Support\Facades\Schema::connection($model->getConnectionName())->hasTable($model->getTable());
            } catch (\Throwable $e) {
                self::$tableReady = false;
            }
        }

        return self::$tableReady;
    }

    /**
     * Offer the purpose to the core's payment requests (no-op on a core without them).
     * WHY a whole-array write: the key contains a dot, which dot-notation would split.
     *
     * @return void
     */
    public static function register(): void
    {
        if (!self::coreHasPaymentRequests()) {
            return;
        }
        $payment = (array) config('gp247-config.payment', []);
        $payment['purposes'] = array_merge((array) ($payment['purposes'] ?? []), [
            self::KEY => [
                'label' => 'Plugins/MultiVendor::lang.payreq_purpose',
                'directions' => ['out'],
                'resolver' => self::class,
                'available' => [self::class, 'available'],
                'subject' => [
                    'types' => [self::SUBJECT_TYPE => 'Plugins/MultiVendor::lang.payreq_subject_label'],
                    'label' => 'Plugins/MultiVendor::lang.payreq_subject_label',
                    'help' => 'Plugins/MultiVendor::lang.payreq_subject_help',
                ],
            ],
        ]);
        config(['gp247-config.payment' => $payment]);
    }

    /**
     * @return bool Whether the core offers payment requests.
     */
    public static function coreHasPaymentRequests(): bool
    {
        return interface_exists(PurposeResolver::class) && class_exists(\GP247\Shop\Payment\PaymentRequestService::class);
    }

    /**
     * Whether the current admin may create payment requests (write on that screen).
     *
     * @return bool
     */
    public static function canCreate(): bool
    {
        if (!self::coreHasPaymentRequests()
            || !\GP247\Shop\Payment\PaymentRequestService::ready()
            || !self::available()
            || !\Illuminate\Support\Facades\Route::has('admin.payment_request.edit')) {
            return false;
        }
        $user = app(\GP247\Core\AdminShell\Domain\AdminUserContract::class);
        $prefix = defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin';

        return $user->isAdministrator() || $user->canAccessUrl($prefix . '/payment_request', 'POST');
    }

    /**
     * @param AdminMoneyProcess $row
     * @return bool Whether a payout request may be raised for the row.
     */
    public static function payable(AdminMoneyProcess $row): bool
    {
        return (string) $row->status === 'processing'
            && (string) ($row->kind ?: Payout::KIND_PERIOD) === Payout::KIND_PERIOD
            && (float) $row->amount > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateRequest(array $data): void
    {
        $row = ($data['subject_type'] ?? null) === self::SUBJECT_TYPE && !empty($data['subject_id'])
            ? AdminMoneyProcess::find($data['subject_id'])
            : null;
        if ($row === null) {
            throw new \GP247\Shop\Payment\Support\RequestRejected($this->lang('payreq_row_missing'), 'subject_id');
        }
        if (!self::payable($row)) {
            throw new \GP247\Shop\Payment\Support\RequestRejected($this->lang('payreq_row_not_payable'), 'subject_id');
        }
        if (strcasecmp((string) $row->currency, (string) ($data['currency'] ?? '')) !== 0) {
            throw new \GP247\Shop\Payment\Support\RequestRejected(str_replace(':currency', (string) $row->currency, $this->lang('payreq_row_currency')), 'currency');
        }
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount > (float) $row->amount && !PaymentCurrency::same($amount, (float) $row->amount, (string) $row->currency)) {
            throw new \GP247\Shop\Payment\Support\RequestRejected(str_replace(':max', (string) $row->amount, $this->lang('payreq_row_over')), 'amount');
        }
        // The marketplace pays its vendors: the request belongs to the root store.
        if ((string) ($data['store_id'] ?? '') !== (string) GP247_STORE_ID_ROOT) {
            throw new \GP247\Shop\Payment\Support\RequestRejected($this->lang('payreq_root_only'), 'subject_id');
        }
    }

    /**
     * @param PaymentRequest $request
     * @return array{label: string, url?: string, suggested_amount?: float}|null
     */
    public function describeSubject(PaymentRequest $request): ?array
    {
        $row = $this->rowOf($request);
        if ($row === null) {
            return null;
        }
        $out = ['label' => (string) $row->content . ' · ' . $row->date_process, 'suggested_amount' => (float) $row->amount];
        if (\Illuminate\Support\Facades\Route::has('admin_MultiVendorPayment.edit')) {
            $out['url'] = gp247_route_admin('admin_MultiVendorPayment.edit', ['id' => $row->id]);
        }

        return $out;
    }

    /**
     * Mark the payout row done once the request is fully paid.
     *
     * @param PaymentRequest  $request Locked, already re-derived by the core.
     * @param PaymentMovement $movement
     * @return void
     */
    public function onSettled(PaymentRequest $request, PaymentMovement $movement): void
    {
        if (!$request->isSettled()) {
            return; // partial transfer: the row stays open until the rest is paid
        }
        $row = $this->rowOf($request);
        if ($row === null) {
            gp247_report('[multivendor] payment request #' . $request->id . ': payout row ' . $request->subject_id . ' not found');

            return;
        }
        if ((string) $row->status !== 'processing') {
            // Settled another way (batch file, the edit form): never overwrite it.
            gp247_report('[multivendor] payment request #' . $request->id . ' paid payout row ' . $row->id . ' already in status ' . $row->status);

            return;
        }
        $reference = trim((string) $movement->reference) !== '' ? (string) $movement->reference : 'payreq-mv-' . $movement->id;
        PayoutRun::markDone($row, $movement->admin_id ?: 0, mb_substr($reference, 0, 150));
    }

    /**
     * A payout is never "reversed" through a request; nothing to mirror.
     *
     * @param PaymentRequest  $request
     * @param PaymentMovement $movement
     * @return void
     */
    public function onReversed(PaymentRequest $request, PaymentMovement $movement): void
    {
    }

    private function rowOf(PaymentRequest $request): ?AdminMoneyProcess
    {
        return $request->subject_type === self::SUBJECT_TYPE && !empty($request->subject_id)
            ? AdminMoneyProcess::find($request->subject_id)
            : null;
    }

    private function lang(string $key): string
    {
        return (string) gp247_language_render('Plugins/MultiVendor::lang.' . $key);
    }
}
