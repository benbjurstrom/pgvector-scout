<?php

use BenBjurstrom\PgvectorScout\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

pest()->extend(TestCase::class)->use(DatabaseTransactions::class);
