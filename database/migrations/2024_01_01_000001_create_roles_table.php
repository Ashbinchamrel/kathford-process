<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();          // super_admin, verifier, approver, finance, general
            $table->string('display_name');            // "Super Admin", "Verifier", etc.
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // Cannot be deleted if true
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
