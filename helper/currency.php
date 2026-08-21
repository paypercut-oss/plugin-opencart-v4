<?php
// Shared by all 3 Paypercut controllers (catalog + 2 admin). Plain functions,
// required directly — no class, no autoloading, no namespace.
//
// Converts between a decimal amount string (e.g. "19.99") and the currency's
// minor units (e.g. 1999) using OpenCart's own per-currency decimal_place
// setting ($this->currency->getDecimalPlace()) as the source of truth, so
// there is no separate hardcoded currency-scale table to maintain here.

if (!function_exists('paypercut_to_minor_units')) {
    function paypercut_to_minor_units(string $amount, int $exponent): int
    {
        $parts = explode('.', trim($amount), 2);
        $whole = $parts[0] !== '' ? $parts[0] : '0';
        $fraction = str_pad(substr($parts[1] ?? '', 0, $exponent), $exponent, '0');

        $sign = 1;
        if (str_starts_with($whole, '-')) {
            $sign = -1;
            $whole = substr($whole, 1);
        }

        return $sign * ((int)$whole * (10 ** $exponent) + (int)$fraction);
    }
}

if (!function_exists('paypercut_from_minor_units')) {
    function paypercut_from_minor_units(int $amount, int $exponent): string
    {
        if ($exponent === 0) {
            return (string)$amount;
        }

        return number_format($amount / (10 ** $exponent), $exponent, '.', '');
    }
}
