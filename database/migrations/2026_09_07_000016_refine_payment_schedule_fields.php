<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('vendor_id')->nullable()->change();
            $table->string('payment_type', 20)->default('full')->after('source');
            $table->string('account_name')->nullable()->after('activity_reference');
            $table->string('sub_account')->nullable()->after('account_name');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'account_name', 'sub_account']);
            $table->foreignUuid('vendor_id')->nullable(false)->change();
        });
    }
};
