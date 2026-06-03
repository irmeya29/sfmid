<?php

namespace App\Support;

class NumberFormatter
{
    public static function quantity(mixed $value, int $maxDecimals = 3): string
    {
        $number = (float) $value;

        if ($number == floor($number)) {
            return number_format($number, 0, ',', ' ');
        }

        $formatted = number_format($number, $maxDecimals, ',', ' ');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    public static function moneyToWords(mixed $value, string $currency = 'FCFA'): string
    {
        $amount = (int) round((float) $value);

        if ($amount === 0) {
            return 'zero '.$currency;
        }

        return self::numberToFrenchWords($amount).' '.$currency;
    }

    private static function numberToFrenchWords(int $number): string
    {
        if ($number < 0) {
            return 'moins '.self::numberToFrenchWords(abs($number));
        }

        $units = [
            0 => 'zero',
            1 => 'un',
            2 => 'deux',
            3 => 'trois',
            4 => 'quatre',
            5 => 'cinq',
            6 => 'six',
            7 => 'sept',
            8 => 'huit',
            9 => 'neuf',
            10 => 'dix',
            11 => 'onze',
            12 => 'douze',
            13 => 'treize',
            14 => 'quatorze',
            15 => 'quinze',
            16 => 'seize',
        ];

        if ($number <= 16) {
            return $units[$number];
        }

        if ($number < 20) {
            return 'dix-'.self::numberToFrenchWords($number - 10);
        }

        if ($number < 100) {
            $tens = [
                20 => 'vingt',
                30 => 'trente',
                40 => 'quarante',
                50 => 'cinquante',
                60 => 'soixante',
            ];

            if ($number < 70) {
                $ten = intdiv($number, 10) * 10;
                $rest = $number % 10;

                if ($rest === 0) {
                    return $tens[$ten];
                }

                return $tens[$ten].($rest === 1 ? ' et ' : '-').self::numberToFrenchWords($rest);
            }

            if ($number < 80) {
                $rest = $number - 60;

                return 'soixante'.($rest === 11 ? ' et ' : '-').self::numberToFrenchWords($rest);
            }

            $rest = $number - 80;

            if ($rest === 0) {
                return 'quatre-vingts';
            }

            return 'quatre-vingt-'.self::numberToFrenchWords($rest);
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $rest = $number % 100;
            $prefix = $hundreds === 1 ? 'cent' : self::numberToFrenchWords($hundreds).' cent';

            if ($rest === 0) {
                return $prefix.($hundreds > 1 ? 's' : '');
            }

            return $prefix.' '.self::numberToFrenchWords($rest);
        }

        foreach ([1000000000 => 'milliard', 1000000 => 'million', 1000 => 'mille'] as $scale => $label) {
            if ($number >= $scale) {
                $count = intdiv($number, $scale);
                $rest = $number % $scale;
                $plural = $scale !== 1000 && $count > 1 ? 's' : '';
                $prefix = $scale === 1000 && $count === 1 ? 'mille' : self::numberToFrenchWords($count).' '.$label.$plural;

                if ($rest === 0) {
                    return $prefix;
                }

                return $prefix.' '.self::numberToFrenchWords($rest);
            }
        }

        return (string) $number;
    }
}
