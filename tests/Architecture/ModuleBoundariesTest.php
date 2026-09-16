<?php

arch('Reporting solo depende de Shared')
    ->expect('App\Reporting')
    ->not->toUse(['App\Catalog', 'App\Inventory', 'App\Ordering', 'App\Publishing']);

arch('Catalog solo depende de Shared')
    ->expect('App\Catalog')
    ->not->toUse(['App\Inventory', 'App\Ordering', 'App\Publishing', 'App\Reporting']);

arch('Inventory no depende de Ordering, Publishing ni Reporting')
    ->expect('App\Inventory')
    ->not->toUse(['App\Ordering', 'App\Publishing', 'App\Reporting']);

arch('Ordering no depende de Publishing ni Reporting')
    ->expect('App\Ordering')
    ->not->toUse(['App\Publishing', 'App\Reporting']);

arch('Publishing no depende de Inventory')
    ->expect('App\Publishing')
    ->not->toUse('App\Inventory');

arch('Publishing conoce Reporting solo a través de sus contratos')
    ->expect('App\Publishing')
    ->not->toUse([
        'App\Reporting\Models',
        'App\Reporting\Services',
        'App\Reporting\Transmitters',
        'App\Reporting\Livewire',
    ]);

arch('Shared no depende de ningún módulo')
    ->expect('App\Shared')
    ->not->toUse(['App\Catalog', 'App\Inventory', 'App\Ordering', 'App\Publishing', 'App\Reporting']);
