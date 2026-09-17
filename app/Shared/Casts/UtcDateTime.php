<?php

namespace App\Shared\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Para columnas timestamptz. Eloquent formatea las fechas sin offset ('Y-m-d H:i:s'), así que
 * Postgres las interpreta con la zona de la sesión; este cast escribe siempre en UTC con offset
 * explícito y lee devolviendo el instante en UTC.
 *
 * @implements CastsAttributes<CarbonImmutable, DateTimeInterface|string>
 */
final class UtcDateTime implements CastsAttributes
{
    private const STORAGE_FORMAT = 'Y-m-d H:i:sP';

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        return $this->toUtc($key, $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->toUtc($key, $value)->format(self::STORAGE_FORMAT);
    }

    private function toUtc(string $key, mixed $value): CarbonImmutable
    {
        if (! is_string($value) && ! $value instanceof DateTimeInterface) {
            throw new InvalidArgumentException("El atributo {$key} debe ser una fecha o una cadena de fecha.");
        }

        return CarbonImmutable::parse($value)->utc();
    }
}
