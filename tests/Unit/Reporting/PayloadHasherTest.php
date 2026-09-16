<?php

use App\Reporting\Services\PayloadHasher;

it('ordena las claves asociativas en todos los niveles y deja las listas intactas', function () {
    $canonical = (new PayloadHasher)->canonicalize([
        'b' => 1,
        'a' => ['y' => [3, 1, 2], 'x' => 'z'],
    ]);

    expect($canonical)->toBe([
        'a' => ['x' => 'z', 'y' => [3, 1, 2]],
        'b' => 1,
    ]);
});

it('produce el mismo hash sin importar el orden de las claves, incluso anidadas', function () {
    $hasher = new PayloadHasher;

    $a = ['b' => 1, 'a' => ['y' => 2, 'x' => 3]];
    $b = ['a' => ['x' => 3, 'y' => 2], 'b' => 1];

    expect($hasher->hash($a))->toBe($hasher->hash($b));
});

it('distingue el orden de las listas', function () {
    $hasher = new PayloadHasher;

    expect($hasher->hash(['items' => [1, 2]]))->not->toBe($hasher->hash(['items' => [2, 1]]));
});

it('distingue valores distintos', function () {
    $hasher = new PayloadHasher;

    expect($hasher->hash(['a' => 1]))->not->toBe($hasher->hash(['a' => 2]));
});

it('devuelve un sha256 en hexadecimal', function () {
    expect((new PayloadHasher)->hash(['a' => 1]))->toMatch('/^[0-9a-f]{64}$/');
});

it('lanza JsonException ante UTF-8 inválido', function () {
    (new PayloadHasher)->hash(['a' => "\xB1\x31"]);
})->throws(JsonException::class);
