<?php

use App\Models\Company;
use App\Support\Tenant;
use Illuminate\Support\Carbon;

if (! function_exists('money')) {
    /**
     * Formatea un importe con el símbolo de moneda de la empresa actual.
     */
    function money($amount, ?string $symbol = null, int $decimals = 2): string
    {
        $symbol ??= Tenant::company()?->currency_symbol ?? '$';
        $value = (float) $amount;
        $formatted = number_format(abs($value), $decimals, ',', '.');

        return ($value < 0 ? '-' : '').$symbol.' '.$formatted;
    }
}

if (! function_exists('num')) {
    function num($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}

if (! function_exists('qty')) {
    /**
     * Cantidad sin decimales innecesarios (1,5 / 2 / 0,125).
     */
    function qty($value): string
    {
        $value = round((float) $value, 3);
        $decimals = fmod($value, 1) == 0.0 ? 0 : strlen(rtrim(substr(strrchr(number_format($value, 3, '.', ''), '.'), 1), '0'));

        return number_format($value, $decimals, ',', '.');
    }
}

if (! function_exists('pct')) {
    /**
     * Porcentaje sin ceros innecesarios (19 %, 2,5 %).
     */
    function pct($value): string
    {
        $formatted = rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');

        return $formatted.'%';
    }
}

if (! function_exists('fdate')) {
    function fdate($date, string $format = 'd/m/Y'): string
    {
        if (! $date) {
            return '—';
        }

        return Carbon::parse($date)->locale(app()->getLocale())->translatedFormat($format);
    }
}

if (! function_exists('company')) {
    function company(): ?Company
    {
        return Tenant::company();
    }
}

if (! function_exists('badge_classes')) {
    function badge_classes(string $color): string
    {
        return match ($color) {
            'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'red' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            'yellow' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'blue' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            'purple' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    }
}
