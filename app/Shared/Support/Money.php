<?php

namespace App\Shared\Support;

use InvalidArgumentException;
use RoundingMode;

/**
 * Aritmética de dinero con bcmath. Los cálculos intermedios usan escala 6 y se redondea a
 * 2 decimales (half-up) una sola vez, sobre el resultado final; nunca por línea intermedia.
 */
final class Money
{
    public const SCALE = 6;

    public const DECIMALS = 2;

    public static function round(string $amount): string
    {
        return bcround(self::numeric($amount), self::DECIMALS, RoundingMode::HalfAwayFromZero);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd(self::numeric($a), self::numeric($b), self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub(self::numeric($a), self::numeric($b), self::SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul(self::numeric($a), self::numeric($b), self::SCALE);
    }

    public static function div(string $a, string $b): string
    {
        return bcdiv(self::numeric($a), self::numeric($b), self::SCALE);
    }

    /**
     * Desglosa un importe final (IVA incluido) en neto e IVA. El IVA se obtiene por diferencia
     * para que net + tax == gross cierre siempre, sin importar el redondeo del neto.
     *
     * @return array{net: string, tax: string}
     */
    public static function splitTax(string $gross, string $taxRate): array
    {
        $divisor = self::add('1', self::div($taxRate, '100'));
        $net = self::round(self::div($gross, $divisor));
        $tax = bcsub(self::numeric(self::round($gross)), self::numeric($net), self::DECIMALS);

        return ['net' => $net, 'tax' => $tax];
    }

    /**
     * @return numeric-string
     */
    private static function numeric(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("«{$value}» no es un importe válido.");
        }

        return $value;
    }
}
