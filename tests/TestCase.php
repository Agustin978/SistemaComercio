<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI no compila assets: sin esto @vite lanza ViteManifestNotFoundException.
        $this->withoutVite();
    }

    /**
     * Se ejecuta antes de que RefreshDatabase/DatabaseTruncation toquen la base.
     *
     * @return array<string, string>
     */
    protected function setUpTraits(): array
    {
        $this->ensureTestingDatabase();

        return parent::setUpTraits();
    }

    // .env.testing está ignorado por git: sin él, APP_ENV=testing cae al .env de desarrollo y migrate:fresh la borraría.
    private function ensureTestingDatabase(): void
    {
        $database = Config::string('database.connections.pgsql.database');

        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException("Los tests solo corren contra una base *_testing, no contra {$database}.");
        }
    }
}
