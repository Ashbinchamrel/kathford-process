<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('rfq_quote_items')
            ->orderBy('id')
            ->chunk(100, function ($quoteItems): void {
                $lineItems = DB::table('form_line_items')
                    ->whereIn('id', $quoteItems->pluck('line_item_id')->filter())
                    ->get(['id', 'item_name', 'quantity', 'unit'])
                    ->keyBy('id');

                foreach ($quoteItems as $quoteItem) {
                    $lineItem = $lineItems->get($quoteItem->line_item_id);
                    if (! $lineItem) {
                        continue;
                    }

                    DB::table('rfq_quote_items')->where('id', $quoteItem->id)->update([
                        'description' => $lineItem->item_name,
                        'quantity'    => $lineItem->quantity,
                        'unit'        => $lineItem->unit,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Retain historical quotation details when rolling back.
    }
};
