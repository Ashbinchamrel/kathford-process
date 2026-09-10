<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_email')->nullable();  // Snapshot in case user is deleted

            $table->string('action', 100);
            // auth.login|auth.logout|auth.2fa_setup|form.created|form.approved|
            // form.rejected|vendor.created|user.created|user.role_changed|po.generated|payment.marked_paid

            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();
            $table->string('model_label')->nullable();  // Human-readable e.g. "PAA-2081-0042"

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();

            $table->timestamp('logged_at')->useCurrent();
            // NO updated_at — audit logs are append-only, never modified

            $table->index(['user_id', 'logged_at']);
            $table->index(['action', 'logged_at']);
            $table->index(['model_type', 'model_id']);
        });

        // In-app notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();    // URL to the relevant form/record
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
    }
};
