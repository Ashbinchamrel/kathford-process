<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('attachable_type');
            $table->uuid('attachable_id');

            $table->string('original_name');
            $table->string('disk_path');          // Storage path (private, outside web root)
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');  // bytes
            $table->string('type')->default('document'); // document|image|invoice|other
            $table->text('external_link')->nullable();   // Optional external URL instead of file

            $table->foreignUuid('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_attachments');
    }
};
