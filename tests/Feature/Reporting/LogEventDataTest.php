<?php

use App\Reporting\Contracts\Data\LogEventData;
use App\Reporting\Contracts\LogLevel;
use Illuminate\Validation\ValidationException;

/**
 * @return array<string, string>
 */
function logEventInput(string $occurredAt): array
{
    return ['level' => 'info', 'message' => 'Evento', 'occurredAt' => $occurredAt];
}

it('rechaza occurredAt sin offset explícito', function () {
    LogEventData::validateAndCreate(logEventInput('2026-09-16T10:00:00'));
})->throws(ValidationException::class);

it('acepta cualquier escritura de offset explícito y la conserva', function (string $input, int $offsetSeconds) {
    $data = LogEventData::validateAndCreate(logEventInput($input));

    expect($data->level)->toBe(LogLevel::Info)
        ->and($data->occurredAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($data->occurredAt->getOffset())->toBe($offsetSeconds)
        ->and($data->occurredAt->format('Y-m-d H:i'))->toBe('2026-09-16 10:00');
})->with([
    'Z' => ['2026-09-16T10:00:00Z', 0],
    '+00:00' => ['2026-09-16T10:00:00+00:00', 0],
    '-03:00' => ['2026-09-16T10:00:00-03:00', -10800],
    '+0000' => ['2026-09-16T10:00:00+0000', 0],
    'milisegundos y Z' => ['2026-09-16T10:00:00.000Z', 0],
]);

it('serializa occurredAt con su offset, que es lo que entra al hash', function () {
    $data = new LogEventData(
        level: LogLevel::Info,
        message: 'Evento',
        occurredAt: new DateTimeImmutable('2026-09-16T10:00:00-03:00'),
    );

    expect($data->toArray()['occurredAt'])->toBe('2026-09-16T10:00:00-03:00');
});
