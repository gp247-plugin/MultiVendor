<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use App\GP247\Plugins\MultiVendor\Payout\Payout;
use App\GP247\Plugins\MultiVendor\Payout\PayoutRun;
use App\GP247\Plugins\MultiVendor\Payout\VendorPayoutPurpose;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Root-admin vendor payout manager (v2 Livewire port of
 * AdminRootVendorPaymentController: index + process + edit/postEdit + delete).
 *
 * Money-critical screen: the payout algorithm and the "paid" step live in
 * Payout\PayoutRun (ported verbatim from the controller, then moved out of
 * this component so a seeder or a test runs the same code); the screen keeps
 * the authorization, the edit form and the per-currency summary.
 *
 * Layer-2 authorization (ADR-001): read is checked on mount(); process(), save()
 * and delete() re-check as mutations. $screenUri is pinned to the canonical
 * payment resource path in boot() so the decision is deterministic no matter
 * which route (index or edit/{id}) or the shared Livewire endpoint invoked it.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-root-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorPaymentManager extends GP247AdminComponent
{
    use WithPagination;

    /**
     * Human-readable permission label only; the access decision is by URI+method
     * (ADR-001 Layer-2), gated against $screenUri.
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_MultiVendorPayment';

    /**
     * Store-id filter (kept in the query string so pagination and Livewire
     * updates preserve it). Mirrors the controller's `keyword` search.
     *
     * @var string
     */
    #[Url]
    public string $keyword = '';

    /**
     * Processing date for a new payout run (the "date_process" the controller's
     * process() validated and grouped orders up to).
     *
     * @var string
     */
    public string $processDate = '';

    /**
     * Id of the money-process row currently being edited, or null in list mode.
     *
     * @var string|null
     */
    public ?string $editId = null;

    /**
     * Edit-form state — the same eight columns AdminRootVendorPaymentController::postEdit() wrote.
     *
     * @var array<string, mixed>
     */
    public array $form = [
        'content' => '',
        'comment' => '',
        'total_sum' => '',
        'order_count' => '',
        'amount' => '',
        'currency' => '',
        'date_process' => '',
        'status' => '',
        'payout_reference' => '',
    ];

    /**
     * Pin the authorization URI to the canonical payment resource path.
     *
     * WHY boot(): it runs before mount()/hydrate on every request, so both the
     * view check (mount) and mutating actions authorize against the same path —
     * including the edit route (payment/edit/{id}) and the /livewire/update
     * endpoint, whose raw request paths would otherwise differ.
     *
     * @return void
     */
    public function boot(): void
    {
        $prefix = defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin';
        $this->screenUri = $prefix.'/MultiVendor/payment';
    }

    /**
     * Enter edit mode when an id is supplied (edit route), otherwise list mode.
     *
     * WHY the `: void` signature (no return value): the parent GP247AdminComponent
     * declares `mount(): void`, so this override must stay void — the missing-row
     * redirect is raised via abort(redirect(...)) instead of being returned.
     *
     * @param string|null $id Money-process row id, or null for the list screen.
     * @return void
     */
    public function mount($id = null): void
    {
        parent::mount();

        if ($id !== null) {
            $payment = AdminMoneyProcess::find($id);
            // WHY redirect (v1 returned a bare "no data" string): keep the admin
            // inside the shell instead of rendering an orphan page.
            if ($payment === null) {
                abort(redirect(gp247_route_admin('admin_MultiVendorPayment.index')));
            }

            $this->editId = $payment->id;
            $this->form = [
                'content' => $payment->content,
                'comment' => $payment->comment,
                'total_sum' => $payment->total_sum,
                'order_count' => $payment->order_count,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'date_process' => $payment->date_process,
                'status' => $payment->status,
                'payout_reference' => (string) ($payment->payout_reference ?? ''),
            ];
        }
    }

    /**
     * Reset paging when the store filter changes so results start at page 1.
     *
     * @return void
     */
    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    /**
     * Run a payout for every store/currency with completed orders in the period.
     *
     * The algorithm lives in PayoutRun::process() (shared with any other caller);
     * this action only authorizes, reports a refusal and refreshes the list.
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function process(): void
    {
        $this->authorizeAction('process');

        $result = PayoutRun::process((string) $this->processDate);
        if ($result['error'] !== null) {
            $this->notify('error', gp247_language_render($result['error']));

            return;
        }

        $this->processDate = '';
        $this->resetPage();
        $this->notify('success', gp247_language_render('action.update_success'));
    }

    /**
     * Persist edits to a money-process row (v2 port of postEdit()).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function save(): void
    {
        $this->authorizeAction('save');

        $payment = AdminMoneyProcess::find($this->editId);
        if ($payment === null) {
            $this->notify('error', gp247_language_render('admin.no_data'));

            return;
        }

        $this->validate([
            'form.content' => 'required|string|max:255',
            'form.comment' => 'nullable|string|max:255',
            'form.total_sum' => 'required|numeric|min:0',
            'form.order_count' => 'required|integer|min:0',
            'form.amount' => 'required|numeric|min:0',
            'form.currency' => 'required|string',
            'form.date_process' => 'required|date',
            'form.status' => 'required|string|max:50',
            'form.payout_reference' => 'nullable|string|max:150',
        ]);

        // WHY gp247_clean like v1: strip disallowed markup from admin-entered
        // strings before persisting (same signature the controller used).
        $data = gp247_clean($this->form, ['password'], true);
        // Moving the row to `done` stamps paid_by/date_pay and mails the vendor (PayoutRun).
        PayoutRun::update($payment, $data, $this->adminIdForLedger());

        session()->flash('gp247_admin_success', gp247_language_render('action.edit_success'));
        $this->redirect(gp247_route_admin('admin_MultiVendorPayment.index'));
    }

    /**
     * Raise a core payment request (money out) for the row being edited and open it, so
     * whoever holds the money-out permission records the transfer; the row turns done
     * when the request is fully paid (VendorPayoutPurpose).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     *
     * @aidlc-story US-multi-vendor-pro-payout-payment-request
     */
    public function createPaymentRequest(): void
    {
        $this->authorizeAction('save');
        $payment = $this->editId !== null ? AdminMoneyProcess::find($this->editId) : null;
        if ($payment === null || !$this->canCreatePaymentRequest()) {
            return;
        }

        try {
            $request = app(\GP247\Shop\Payment\PaymentRequestService::class)->create([
                'direction' => 'out',
                'purpose' => VendorPayoutPurpose::KEY,
                'amount' => (float) $payment->amount,
                'currency' => (string) $payment->currency,
                'subject_type' => VendorPayoutPurpose::SUBJECT_TYPE,
                'subject_id' => (string) $payment->id,
                'store_id' => (string) GP247_STORE_ID_ROOT,
                'party_name' => function_exists('gp247_store_info') ? (string) (gp247_store_info('title', null, $payment->store_id) ?: $payment->store_id) : (string) $payment->store_id,
                'description' => (string) $payment->content,
                'created_by' => (string) $this->adminIdForLedger() ?: null,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->notify('error', $e->getMessage());

            return;
        }

        $this->redirect(route('admin.payment_request.edit', ['id' => $request->id]));
    }

    /**
     * Whether a payout request can be raised for the row being edited by this admin.
     *
     * @return bool
     */
    public function canCreatePaymentRequest(): bool
    {
        if ($this->editId === null || !VendorPayoutPurpose::canCreate()) {
            return false;
        }
        $payment = AdminMoneyProcess::find($this->editId);

        return $payment !== null && VendorPayoutPurpose::payable($payment);
    }

    /** Admin id for the ledger's paid_by column (0 when unavailable). */
    private function adminIdForLedger()
    {
        if (function_exists('admin') && admin()->user()) {
            return admin()->user()->id;
        }

        return 0;
    }

    /**
     * Delete one money-process row (v2 port of deleteList(), single id).
     *
     * @param string $id Money-process row id.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function delete(string $id): void
    {
        $this->authorizeAction('delete');

        AdminMoneyProcess::destroy($id);

        $this->notify('success', gp247_language_render('action.delete_confirm_deleted_msg'));
    }

    /**
     * Render the list (with the per-currency summary) or the edit form.
     *
     * @return View
     */
    public function render(): View
    {
        // Tailwind tint per payment status — chosen at runtime, so the values are
        // kept on the plugin's Tailwind safelist (same map the v1 controller used).
        $mapcolor = [
            'done' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
            'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
            'canceled' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
        ];

        $currencyOptions = collect(gp247_currency_all_active())
            ->map(fn ($name, $key) => ['id' => $key, 'label' => $name])
            ->values()
            ->all();

        // List mode only: build the paginated rows and the per-currency summary.
        $payments = null;
        $dataAmount = [];
        if ($this->editId === null) {
            $query = new AdminMoneyProcess;
            if ($this->keyword !== '') {
                $query = $query->where('store_id', gp247_clean($this->keyword));
            }
            $payments = $query->orderBy('date_process', 'desc')->paginate(20);

            // Per-currency totals — identical helpers to the v1 index().
            if ($sumAmount = gp247_cal_amount_order_done()) {
                foreach ($sumAmount as $row) {
                    $dataAmount[$row['currency']]['sumAmount'] = $row['total_sum'];
                }
            }
            if ($sumAmountDone = gp247_cal_amount_payment_done()) {
                foreach ($sumAmountDone as $row) {
                    $dataAmount[$row['currency']]['sumAmountDone'] = $row['amount'];
                }
            }
            if ($sumAmountRemaining = gp247_cal_amount_payment_remaining()) {
                foreach ($sumAmountRemaining as $row) {
                    $dataAmount[$row['currency']]['sumAmountRemaining'] = $row['amount'];
                }
            }
        }

        return view('Plugins/MultiVendor::Admin.screen.root.livewire.vendor_payment_manager', [
            // S5-2: settling a whole batch is a paid feature — its panel is a nested
            // component the paid plugin registers; null shows the upgrade hint.
            'batchPanel' => Payout::batchPanel(),
            'payments' => $payments,
            'dataAmount' => $dataAmount,
            'canStatement' => \App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT) && \Illuminate\Support\Facades\Route::has('admin_MultiVendorPayment.export'),
            'mapcolor' => $mapcolor,
            'currencyOptions' => $currencyOptions,
            'statusOptions' => ['processing', 'pending', 'canceled', 'done'],
        ])->layout('gp247-admin::layouts.admin', [
            'title' => $this->editId === null
                ? gp247_language_render('multi_vendor.vendor_payment')
                : gp247_language_render('action.edit'),
        ]);
    }
}
