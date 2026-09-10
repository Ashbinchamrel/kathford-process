<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sequences = DB::table('payment_authorisations')
            ->where('authorisation_number', 'like', 'PA-%')
            ->selectRaw('CAST(SUBSTRING(authorisation_number, 4, 4) AS UNSIGNED) AS calendar_year')
            ->selectRaw('MAX(CAST(SUBSTRING(authorisation_number, 9) AS UNSIGNED)) AS last_number')
            ->groupBy('calendar_year')
            ->get();

        foreach ($sequences as $sequence) {
            DB::table('document_number_sequences')->upsert([
                [
                    'document_type' => 'payment_authorisation',
                    'calendar_year' => $sequence->calendar_year,
                    'last_number' => $sequence->last_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ], ['document_type', 'calendar_year'], ['last_number', 'updated_at']);
        }
    }

    public function down(): void
    {
        DB::table('document_number_sequences')->where('document_type', 'payment_authorisation')->delete();
    }
};
