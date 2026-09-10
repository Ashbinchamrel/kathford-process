<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40);
            $table->unsignedSmallInteger('calendar_year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['document_type', 'calendar_year']);
        });

        // Preserve the next value after every existing (including soft-deleted)
        // payment reference so no historic reference can ever be reused.
        $existingSequences = DB::table('payments')
            ->where('payment_number', 'like', 'PAY-%')
            ->selectRaw('CAST(SUBSTRING(payment_number, 5, 4) AS UNSIGNED) AS calendar_year')
            ->selectRaw('MAX(CAST(SUBSTRING(payment_number, 10) AS UNSIGNED)) AS last_number')
            ->groupBy('calendar_year')
            ->get();

        foreach ($existingSequences as $sequence) {
            DB::table('document_number_sequences')->insert([
                'document_type' => 'payment',
                'calendar_year' => $sequence->calendar_year,
                'last_number' => $sequence->last_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
