# Pgvector driver for Laravel Scout

Use pgvector with Laravel Scout for fast vector similarity search.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/benbjurstrom/pgvector-scout.svg?style=flat-square)](https://packagist.org/packages/benbjurstrom/pgvector-scout)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/benbjurstrom/pgvector-scout/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/benbjurstrom/pgvector-scout/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/benbjurstrom/pgvector-scout/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/benbjurstrom/pgvector-scout/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/benbjurstrom/pgvector-scout.svg?style=flat-square)](https://packagist.org/packages/benbjurstrom/pgvector-scout)

## 🚀 Quick Start

#### 1. Install the package using composer:
```bash
composer require benbjurstrom/pgvector-scout
```

#### 2. Publish and run the migrations:
```bash
php artisan vendor:publish --tag="pgvector-scout-migrations"
php artisan migrate
```

#### 3. Ensure the pgvector extension is available:
```sql
select * from pg_extension where extname='vector';
```

#### 4. Update the model you wish to make searchable:
Add the `HasEmbeddings` and `Searchable` traits to your model and implement `toSearchableArray()` with the content you want converted into an embedding.

```php
use BenBjurstrom\PgvectorScout\Models\Concerns\HasEmbeddings;
use Laravel\Scout\Searchable;

class YourModel extends Model
{
    use HasEmbeddings, Searchable;

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
```

#### 5. Configure your environment:
If you're using OpenAI to generate your embeddings be sure to add your API key to your `.env` file:
```env
OPENAI_API_KEY=your-api-key
```

#### 6. Publish the config:
```bash
php artisan vendor:publish --tag="pgvector-scout-config"
```

This is the contents of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Embedding Handler
    |--------------------------------------------------------------------------
    |
    | This option controls which embedding handler to use by default. You can
    | change this to any of the handlers defined below or create your own.
    |
    */
    'default' => env('EMBEDDING_HANDLER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Handler Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can define the configuration for different embedding handlers.
    | Each handler can have its own specific configuration options.
    |
    */
    'handlers' => [
        'openai' => [
            'class' => \BenBjurstrom\PgvectorScout\Handlers\OpenAiHandler::class,
            'model' => 'text-embedding-3-small',
            'dimensions' => 1536,
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'table' => 'embeddings',
        ],
        'fake' => [
            'class' => \BenBjurstrom\PgvectorScout\Handlers\FakeHandler::class,
            'model' => 'fake',
            'dimensions' => 3,
            'url' => 'https://example.com',
            'api_key' => '123',
            'table' => 'embeddings',
        ],
    ],
];
```

## 🔍 Usage

### Create embeddings for your models:
Scout will automatically generate embeddings for your models when they are saved. If you want to manually generate embeddings for existing models you can use the artisan command below. See the [Scout documentation](https://laravel.com/docs/8.x/scout) for more information.

```bash
artisan scout:import "App\Models\YourModel"
```

### Search your models using vector similarity:
```php
// Search using a text query
$results = YourModel::search('your search query')->get();
```

The text of your query will be converted into an embedding using the configured embedding handler.

You can also search using an existing embedding vector to find related models:
```php
$vector = $someModel->embedding->vector;
$results = YourModel::search($vector)->get();
```

All search results will be ordered by similarity to the query and include the embedding relationship. The value of the nearest neighbor search can be accessed as follows:
```php
$results = YourModel::search('your search query')->get();
$results->first()->embedding->neighbor_distance; // 1.0121312 (example value)
```

## Installing pgvector when using DBngin
First, add PostgreSQL to your path:
```bash
export PATH=/Users/Shared/DBngin/postgresql/14.3/bin:$PATH
```

Then install pgvector:
```bash
git clone https://github.com/pgvector/pgvector.git
cd pgvector
make && make install
```

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 👏 Credits

- [Ben Bjurstrom](https://github.com/benbjurstrom)
- [All Contributors](../../contributors)

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
