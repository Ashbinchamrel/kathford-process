<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('portal_enabled')->default(false)->after('is_active');
            $table->string('portal_password')->nullable()->after('email');
            $table->timestamp('portal_password_changed_at')->nullable()->after('portal_password');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_password_changed_at');
            $table->index(['portal_enabled', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['portal_enabled', 'is_active']);
            $table->dropColumn([
                'portal_enabled', 'portal_password', 'portal_password_changed_at', 'portal_last_login_at',
            ]);
        });
    }
};
