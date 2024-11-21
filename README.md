<p align="center"><img src="https://github.com/user-attachments/assets/9c55eb67-f44f-442d-86b9-e0969213862c" width="600" alt="Logo"></a></p>

<p align="center">
<a href="https://packagist.org/packages/benbjurstrom/pgvector-scout"><img src="https://img.shields.io/packagist/v/benbjurstrom/pgvector-scout.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://github.com/benbjurstrom/pgvector-scout/actions?query=workflow%3Arun-tests+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/benbjurstrom/pgvector-scout/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
<a href="https://github.com/benbjurstrom/pgvector-scout/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/benbjurstrom/pgvector-scout/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square" alt="GitHub Code Style Action Status"></a>
</p>

# Pgvector driver for Laravel Scout

Use the pgvector extension with Laravel Scout for vector similarity search.

To see a full example showing how to use this package check out [benbjurstrom/pgvector-scout-demo](https://github.com/benbjurstrom/pgvector-scout-demo).

## 🚀 Quick Start

#### 1. Install the package using composer:
```bash
composer require benbjurstrom/pgvector-scout
```

### 2. Publish the scout config and the package config:
```bash
php artisan vendor:publish --tag="scout-config"
php artisan vendor:publish --tag="pgvector-scout-config"
```

This is the contents of the published `pgvector-scout.php` config file. By default it contains 3 different indexes, one for OpenAI, one for Gemini, and one for testing. The rest of this guide will use the OpenAI index as an example.

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Embedding Index Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can define the configuration for different embedding indexes.
    | Each index can have its own specific configuration options.
    |
    */
    'indexes' => [
        'openai' => [
            'handler' => \BenBjurstrom\PgvectorScout\Handlers\OpenAiHandler::class,
            'model' => 'text-embedding-3-small',
            'dimensions' => 256, // See Reducing embedding dimensions https://platform.openai.com/docs/guides/embeddings#use-cases
            'url' => 'https://api.openai.com/v1',
            'api_key' => env('OPENAI_API_KEY'),
            'table' => 'openai_embeddings',
        ],
        'gemini' => [
            'handler' => \BenBjurstrom\PgvectorScout\Handlers\GeminiHandler::class,
            'model' => 'text-embedding-004',
            'dimensions' => 256,
            'url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => env('GEMINI_API_KEY'),
            'table' => 'gemini_embeddings',
            'task' => 'SEMANTIC_SIMILARITY', // https://ai.google.dev/api/embeddings#tasktype
        ],
        'fake' => [ // Used for testing
            'handler' => \BenBjurstrom\PgvectorScout\Handlers\FakeHandler::class,
            'model' => 'fake',
            'dimensions' => 3,
            'url' => 'https://example.com',
            'api_key' => '123',
            'table' => 'fake_embeddings',
        ],
    ],
];
```

### 3. Set the scout driver to `pgvector` in your `.env` file and add your OpenAI API key:
```env
SCOUT_DRIVER=pgvector
OPENAI_API_KEY=your-api-key
```

#### 4. Run the scout index command to create a migration file for your embeddings

In this case we'll use the `openai` index.

```bash
php artisan scout:index openai
php artisan migrate
```

#### 5. Update the model you wish to make searchable:
Add the `HasEmbeddings` and `Searchable` traits to your model. Additionally add a `searchableAs()` method that returns the name of your index. Again we're using the `openai` index. Finally implement `toSearchableArray()` with the content from the model you want converted into an embedding.

```php
use BenBjurstrom\PgvectorScout\Models\Concerns\HasEmbeddings;
use Laravel\Scout\Searchable;

class YourModel extends Model
{
    use HasEmbeddings, Searchable;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'openai';
    }

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

## 🔍 Usage

### Create embeddings for your models:
Laravel Scout uses eloquent model observers to automatically keep your search index in sync anytime your Searchable models change. 

This package uses this functionality automatically generate embeddings for your models when they are saved or updated; or remove them when your models are deleted.

If you want to manually generate embeddings for existing models you can use the artisan command below. See the [Scout documentation](https://laravel.com/docs/8.x/scout) for more information.

```bash
artisan scout:import "App\Models\YourModel"
```

### Search using vector similarity:
You can use the typical Scout syntax to search your models. For example:

```php
$results = YourModel::search('your search query')->get();
```

Note that the text of your query will be converted into a vector embedding using the model index's configured handler. It's important that the same model is used for both indexing and searching.

### Search using existing vectors:
You can also pass an existing embedding vector as a search parameter. This can be useful to find related models. For example:
```php
$vector = $someModel->embedding->vector;
$results = YourModel::search($vector)->get();
```

### Evaluate search results:
All search queries will be ordered by similarity to the given input and include the embedding relationship. The value of the nearest neighbor search can be accessed as follows:
```php
$results = YourModel::search('your search query')->get();
$results->first()->embedding->neighbor_distance; // 0.26834 (example value)
```

The larger the distance the less similar the result is to the input.

## 🛠Using custom handlers
By default this package uses OpenAI to generate embeddings. To do this it uses the [OpenAiHandler](https://github.com/benbjurstrom/pgvector-scout/blob/main/src/Handlers/OpenAiHandler.php) class paired with the openai index found in the packages [config file](https://github.com/benbjurstrom/pgvector-scout/blob/main/config/pgvector-scout.php).

You can generate embeddings from other providers by adding a custom Handler. A handler is a simple class defined in the [HandlerContract](https://github.com/benbjurstrom/pgvector-scout/blob/main/src/HandlerContract.php) that takes a string, a config object, and returns a `Pgvector\Laravel\Vector` object.

Whatever api calls or logic is needed to turn a string into a vector should be defined in the `handle` method of your custom handler.

If you need to pass api keys, embedding dimensions, or any other configuration to your handler you can define them in the `config/pgvector-scout.php` file.

## Installing pgvector when using DBngin
If you're using [DBngin](https://dbngin.com/) for local development you can install the pgvector extention by doing the following:

1. Add PostgreSQL to your path:
```bash
export PATH=/Users/Shared/DBngin/postgresql/14.3/bin:$PATH
```

2. Then install pgvector:
```bash
git clone https://github.com/pgvector/pgvector.git
cd pgvector
make && make install
```

## 👏 Credits

- [Ben Bjurstrom](https://github.com/benbjurstrom)
- [All Contributors](../../contributors)

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
