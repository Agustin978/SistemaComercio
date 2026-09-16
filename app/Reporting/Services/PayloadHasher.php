<?php

namespace App\Reporting\Services;

final class PayloadHasher
{
    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function hash(array $payload): string
    {
        $json = json_encode(
            $this->canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return hash('sha256', $json);
    }

    /**
     * Ordena las claves de los arrays asociativos en todos los niveles; las listas conservan su orden porque es semántico.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function canonicalize(array $payload): array
    {
        $canonical = [];

        foreach ($payload as $key => $value) {
            $canonical[$key] = is_array($value) ? $this->canonicalize($value) : $value;
        }

        if (! array_is_list($canonical)) {
            ksort($canonical, SORT_STRING);
        }

        return $canonical;
    }
}
