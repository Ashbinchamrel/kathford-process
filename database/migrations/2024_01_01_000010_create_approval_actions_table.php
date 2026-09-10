<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Immutable audit trail — every approval action across all form types
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // What was acted upon
            $table->string('actionable_type');
            $table->uuid('actionable_id');

            // Who acted
            $table->foreignUuid('actor_id')->constrained('users');

            // Layer: 1=creator submit, 2=verifier, 3=approver
            $table->tinyInteger('layer')->unsigned();

            // Decision
            $table->string('decision');
            // submitted|approved|modified_approved|rejected|recalled|forwarded

            $table->text('note')->nullable();
            $table->json('changes')->nullable(); // What was modified in modified_approved

            // Immutable timestamp — NOT Laravel's updated_at
            $table->timestamp('acted_at')->useCurrent();

            // Request metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->index(['actionable_type', 'actionable_id']);
            $table->index(['actor_id', 'acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
