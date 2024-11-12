<?php

use BenBjurstrom\PgvectorScout\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);
