<?php

namespace BenBjurstrom\PgvectorScout\Commands;

use Illuminate\Console\Command;

class PgvectorScoutCommand extends Command
{
    public $signature = 'pgvector-scout';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
