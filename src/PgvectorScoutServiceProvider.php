<?php

namespace BenBjurstrom\PgvectorScout;

use Laravel\Scout\EngineManager;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('benbjurstrom/pgvector-scout');
            });
    }

    public function boot(): void
    {
        parent::boot();

        resolve(EngineManager::class)->extend('pgvector', function () {
            return new PgvectorEngine;
        });
    }
}
