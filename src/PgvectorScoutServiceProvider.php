<?php

namespace BenBjurstrom\PgvectorScout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use BenBjurstrom\PgvectorScout\Commands\PgvectorScoutCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Laravel\Scout\EngineManager;

class PgvectorScoutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('pgvector-scout')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_embeddings_table')
            ->runsMigrations()
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->askToRunMigrations();
                    //->askToStarRepoOnGitHub();
            });
    }

    public function boot(): void
    {
        parent::boot();

        resolve(EngineManager::class)->extend('pgvector', function () {
            return new PgvectorEngine();
        });
    }
}
