<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:recurring';

    protected $description = 'Genera las facturas de las plantillas recurrentes vencidas de todas las empresas';

    public function handle(RecurringInvoiceService $service): int
    {
        $count = $service->runDue();
        $this->info("Facturas recurrentes generadas: {$count}");

        return self::SUCCESS;
    }
}
