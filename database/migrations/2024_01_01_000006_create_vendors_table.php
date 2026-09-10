<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Basic info
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('category');
            // Stationery|IT Equipment|Furniture|Services|Catering|Construction|Other
            $table->string('pan_vat_number', 20)->nullable();
            $table->string('company_type')->nullable();
            // Sole Proprietor|Private Ltd|Public Ltd|Partnership|NGO|Other

            // People
            $table->string('owner_name')->nullable();
            $table->string('contact_person');
            $table->string('mobile_number', 20);
            $table->string('office_number', 20)->nullable();

            // Location & contact
            $table->text('address');
            $table->string('email')->nullable();

            // Bank details — bank account number encrypted at rest
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('bank_account_number')->nullable(); // Encrypted via Laravel's Encryptable cast

            // Meta
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'category']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
