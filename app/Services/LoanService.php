<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Genera el cuadro de amortización (sistema francés, cuota fija).
     */
    public function schedule(float $principal, float $annualRate, int $count, string $frequency, Carbon $start): array
    {
        $periodsPerYear = ['weekly' => 52, 'biweekly' => 24, 'monthly' => 12, 'quarterly' => 4][$frequency] ?? 12;
        $rate = $annualRate / 100 / $periodsPerYear;
        $payment = $rate > 0
            ? $principal * $rate / (1 - pow(1 + $rate, -$count))
            : $principal / $count;
        $payment = round($payment, 2);

        $rows = [];
        $balance = $principal;
        $date = $start->copy();

        for ($n = 1; $n <= $count; $n++) {
            $date = match ($frequency) {
                'weekly' => $start->copy()->addWeeks($n),
                'biweekly' => $start->copy()->addDays(15 * $n),
                'quarterly' => $start->copy()->addMonthsNoOverflow(3 * $n),
                default => $start->copy()->addMonthsNoOverflow($n),
            };
            $interest = round($balance * $rate, 2);
            $capital = $n === $count ? round($balance, 2) : round($payment - $interest, 2);
            $balance = round($balance - $capital, 2);

            $rows[] = [
                'number' => $n,
                'due_date' => $date->toDateString(),
                'principal' => $capital,
                'interest' => $interest,
                'amount' => round($capital + $interest, 2),
                'balance_after' => max(0, $balance),
            ];
        }

        return $rows;
    }

    public function regenerate(Loan $loan): void
    {
        DB::transaction(function () use ($loan) {
            $loan->installments()->get()->each->delete();
            $rows = $this->schedule((float) $loan->principal, (float) $loan->interest_rate, (int) $loan->installments_count, $loan->frequency, $loan->start_date);
            $loan->installments()->createMany($rows);
        });
    }
}
