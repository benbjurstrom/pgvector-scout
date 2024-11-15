<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use Ramsey\Uuid\Uuid;

class HashContent
{
    public static function handle(string $data): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_OID, $data)->toString();
    }
}
