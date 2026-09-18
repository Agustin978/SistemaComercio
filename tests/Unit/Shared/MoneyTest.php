<?php

use App\Shared\Support\Money;

it('redondea a dos decimales half-up', function (string $amount, string $expected) {
    expect(Money::round($amount))->toBe($expected);
})->with([
    ['10.005', '10.01'],
    ['10.004', '10.00'],
    ['2.675', '2.68'],
    ['0.001', '0.00'],
    ['1528.925619', '1528.93'],
]);

it('desglosa neto e IVA desde el precio final y siempre cierra contra el total', function (string $gross, string $rate, string $net, string $tax) {
    $split = Money::splitTax($gross, $rate);

    expect($split)->toBe(['net' => $net, 'tax' => $tax])
        ->and(bcadd($split['net'], $split['tax'], 2))->toBe($gross);
})->with([
    'redondo' => ['100.00', '21.00', '82.64', '17.36'],
    'centavo dificil' => ['100.01', '21.00', '82.65', '17.36'],
    'minimo' => ['0.01', '21.00', '0.01', '0.00'],
    'demo' => ['1850.00', '21.00', '1528.93', '321.07'],
    'tasa con decimales' => ['33.33', '10.50', '30.16', '3.17'],
    'sin impuesto' => ['57.90', '0.00', '57.90', '0.00'],
]);

it('opera con escala interna de seis decimales sin pasar por float', function () {
    expect(Money::div('1', '3'))->toBe('0.333333')
        ->and(Money::mul('0.1', '0.2'))->toBe('0.020000')
        ->and(Money::add('0.1', '0.2'))->toBe('0.300000')
        ->and(Money::sub('1', '0.9'))->toBe('0.100000');
});
