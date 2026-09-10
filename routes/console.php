<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kathford:reset-transaction-data {--force : Confirm removal of transactional workflow data}', function () {
    if (! $this->option('force')) {
        $this->error('This command removes workflow transactions. Re-run with --force after confirming the scope.');
        return self::FAILURE;
    }

    $privatePaths = collect()
        ->merge(\App\Models\FormAttachment::query()->pluck('disk_path'))
        ->merge(\App\Models\VendorBill::query()->pluck('disk_path'))
        ->filter()
        ->unique()
        ->values();

    DB::transaction(function () {
        // Child records are cleared before their parents. Master setup remains:
        // users, roles, departments, vendors, payees, payment accounts,
        // approval chains, categories, and organisation settings.
        foreach ([
            'approval_actions',
            'notifications',
            'audit_logs',
            'vendor_bill_items',
            'procurement_checklists',
            'grn_items',
            'goods_received_notes',
        ] as $table) {
            DB::table($table)->delete();
        }

        DB::table('payments')->whereNotNull('parent_payment_id')->delete();
        DB::table('payments')->delete();

        foreach ([
            'payment_authorisations',
            'vendor_bills',
            'purchase_order_items',
            'purchase_orders',
            'rfq_quote_items',
            'rfq_quotes',
            'rfqs',
            'purchase_requests',
            'form_line_items',
            'form_attachments',
            'activity_forms',
            'department_budgets',
        ] as $table) {
            DB::table($table)->delete();
        }

        // Avoid sending delayed emails for workflow records that no longer exist.
        foreach (['jobs', 'failed_jobs'] as $table) {
            if (Schema::hasTable($table)) DB::table($table)->delete();
        }
    });

    foreach ($privatePaths as $path) Storage::disk('private')->delete($path);

    $this->info('Transactional workflow data and related private uploads have been removed. Master setup has been preserved.');
    return self::SUCCESS;
})->purpose('Remove operational workflow entries while keeping master setup');
