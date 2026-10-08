<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Marca como vencidas las suscripciones cuyo periodo terminó';

    public function handle(): int
    {
        $expired = 0;

        Subscription::withoutGlobalScopes()->whereIn('status', ['active', 'trialing'])->get()
            ->reject->isValid()
            ->each(function (Subscription $subscription) use (&$expired) {
                $subscription->update(['status' => 'past_due']);
                $expired++;
            });

        $this->info("Suscripciones vencidas: {$expired}");

        return self::SUCCESS;
    }
}
