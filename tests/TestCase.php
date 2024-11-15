<?php

namespace BenBjurstrom\PgvectorScout\Tests;

use BenBjurstrom\PgvectorScout\PgvectorScoutServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Scout\EngineManager;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'BenBjurstrom\\PgvectorScout\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        $app->singleton(EngineManager::class, function ($app) {
            return new EngineManager($app);
        });

        return [
            PgvectorScoutServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql.username', 'postgres');
        config()->set('scout.driver', 'pgvector');
        config()->set('pgvector-scout.default', 'fake');
    }
}
