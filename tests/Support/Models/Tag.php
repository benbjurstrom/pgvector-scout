<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Models;

use BenBjurstrom\PgvectorScout\Tests\Support\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    protected static function newFactory(): Factory
    {
        return TagFactory::new();
    }

    /**
     * Get the documents that have this tag.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }
}
