<?php

namespace BenBjurstrom\PgvectorScout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use BenBjurstrom\PgvectorScout\Commands\PgvectorScoutCommand;

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
            ->hasMigration('create_pgvector_scout_table')
            ->hasCommand(PgvectorScoutCommand::class);
    }
}
