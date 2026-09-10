<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Configurable approval chain — set by Super Admin from control panel
        // Each form category can have its own chain OR use the default chain
        Schema::create('approval_chains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                   // "Default Chain", "Purchase Chain", etc.
            $table->boolean('is_default')->default(false);
            $table->foreignUuid('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_chains');
    }
};
