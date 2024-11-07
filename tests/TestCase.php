<?php

namespace BenBjurstrom\PgvectorScout\Tests;

use BenBjurstrom\PgvectorScout\Tests\Support\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use BenBjurstrom\PgvectorScout\PgvectorScoutServiceProvider;
use Workbench\Database\Seeders\DatabaseSeeder as Seeder;

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
        return [
            PgvectorScoutServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql.username', 'postgres');

        // Load the embeddings table migration
//        $migration = include __DIR__.'/../database/migrations/create_embeddings_table.php.stub';
//        $migration->up();
//
//        // Load the reviews table migration for testing
//        $migration = include __DIR__.'/Support/Migrations/2024_11_06_150840_create_reviews_table.php';
//        $migration->up();
    }
}
