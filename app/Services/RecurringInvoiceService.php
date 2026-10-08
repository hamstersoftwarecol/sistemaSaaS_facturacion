<?php

namespace App\Services;

use App\Models\Document;
use App\Models\RecurringInvoice;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceService
{
    public function __construct(protected DocumentService $documents) {}

    /**
     * Genera la siguiente factura de una plantilla recurrente.
     */
    public function generate(RecurringInvoice $recurring): Document
    {
        return DB::transaction(function () use ($recurring) {
            $date = $recurring->next_date->copy();

            $invoice = $this->documents->save('invoice', [
                'customer_id' => $recurring->customer_id,
                'branch_id' => $recurring->branch_id,
                'recurring_invoice_id' => $recurring->id,
                'price_list_id' => $recurring->customer?->price_list_id,
                'reference' => $recurring->title,
                'date' => $date->toDateString(),
                'due_date' => $date->copy()->addDays($recurring->due_days)->toDateString(),
                'notes' => $recurring->notes,
                'terms' => $recurring->terms,
                'items' => $recurring->items,
            ]);

            $occurrences = $recurring->occurrences + 1;
            $next = $recurring->advance($date);
            $finished = ($recurring->max_occurrences && $occurrences >= $recurring->max_occurrences)
                || ($recurring->end_date && $next->gt($recurring->end_date));

            $recurring->update([
                'occurrences' => $occurrences,
                'next_date' => $next,
                'last_generated_at' => now(),
                'status' => $finished ? 'completed' : $recurring->status,
            ]);

            return $invoice;
        });
    }

    /**
     * Procesa todas las plantillas vencidas (todas las empresas). Usado por el programador.
     */
    public function runDue(): int
    {
        $count = 0;

        $due = RecurringInvoice::withoutGlobalScopes()
            ->where('status', 'active')->whereDate('next_date', '<=', today())->get();

        foreach ($due as $recurring) {
            Tenant::set($recurring->company);
            $recurring = RecurringInvoice::find($recurring->id);

            while ($recurring->isDue()) {
                $this->generate($recurring);
                $recurring->refresh();
                $count++;
            }
        }

        Tenant::forget();

        return $count;
    }
}
