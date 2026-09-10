<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Allocates payment references without reusing a number after a soft delete
 * and without colliding when more than one payment is created at once.
 */
final class PaymentNumber
{
    public static function next(): string
    {
        return self::nextFor('payment', 'PAY');
    }

    public static function nextPaymentAuthorisation(): string
    {
        return self::nextFor('payment_authorisation', 'PA');
    }

    public static function nextRfq(): string {
        $year=(int) now()->format('Y');
        $maximum=DB::table('rfqs')->where('rfq_number','like',"RFQ-{$year}-%")->pluck('rfq_number')->map(fn($number)=>(int)substr($number,strrpos($number,'-')+1))->max() ?? 0;
        DB::table('document_number_sequences')->insertOrIgnore(['document_type'=>'rfq','calendar_year'=>$year,'last_number'=>$maximum,'created_at'=>now(),'updated_at'=>now()]);
        return self::nextFor('rfq', 'RFQ');
    }

    private static function nextFor(string $documentType, string $prefix): string
    {
        $year = (int) now()->format('Y');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($documentType, $prefix, $year): string {
                    $now = now();
                    $updated = DB::table('document_number_sequences')
                        ->where('document_type', $documentType)
                        ->where('calendar_year', $year)
                        ->update([
                            'last_number' => DB::raw('last_number + 1'),
                            'updated_at' => $now,
                        ]);

                    if ($updated === 0) {
                        DB::table('document_number_sequences')->insert([
                            'document_type' => $documentType,
                            'calendar_year' => $year,
                            'last_number' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $number = 1;
                    } else {
                        $number = (int) DB::table('document_number_sequences')
                            ->where('document_type', $documentType)
                            ->where('calendar_year', $year)
                            ->value('last_number');
                    }

                    return sprintf('%s-%d-%04d', $prefix, $year, $number);
                }, 5);
            } catch (QueryException $exception) {
                // The first record for a year can be requested simultaneously.
                // The unique index selects one writer; the other retries safely.
                if ($attempt === 2 || ! str_contains(strtolower($exception->getMessage()), 'duplicate')) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to allocate a payment number.');
    }
}
