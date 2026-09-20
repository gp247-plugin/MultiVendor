<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers;

use GP247\Core\Controllers\RootAdminController;;
use App\GP247\Plugins\MultiVendor\AppConfig;
use GP247\Core\Controllers\CustomFieldTrait;

class RootVendorController extends RootAdminController
{
    use CustomFieldTrait;


    public $plugin;
    public $templatePathAdmin;
    public function __construct()
    {
        parent::__construct();
        $this->plugin = new AppConfig;
        $this->templatePathAdmin = (new AppConfig)->appPath.'::Admin.';
    }

    /**
     * Per-row action dropdown, TailAdmin flavour.
     *
     * Overrides the parent's Bootstrap `data-toggle="dropdown"` markup, which
     * needs Bootstrap's jQuery plugin to open — core 2.x loads neither. Alpine
     * (shipped with Livewire) drives the disclosure instead.
     *
     * @param array<int, string> $arrAction rendered <a> items.
     */
    public function procesListAction(array $arrAction)
    {
        if (!count($arrAction)) {
            return '';
        }

        return '<div class="relative inline-block text-left" x-data="{ open: false }">
            <button type="button" x-on:click="open = !open"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div x-show="open" x-cloak x-on:click.outside="open = false"
                class="absolute end-0 z-20 mt-1 w-44 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                ' . implode('', $arrAction) . '
            </div>
        </div>';
    }

    /**
     * Shared class list for the items inside procesListAction()'s dropdown —
     * the TailAdmin replacement for Bootstrap's `.dropdown-item`.
     */
    public function actionItemClass(): string
    {
        return 'flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition '
            . 'hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700';
    }
}
