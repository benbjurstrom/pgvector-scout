# Pgvector driver for Laravel Scout

[![Latest Version on Packagist](https://img.shields.io/packagist/v/benbjurstrom/pgvector-scout.svg?style=flat-square)](https://packagist.org/packages/benbjurstrom/pgvector-scout)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/benbjurstrom/pgvector-scout/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/benbjurstrom/pgvector-scout/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/benbjurstrom/pgvector-scout/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/benbjurstrom/pgvector-scout/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/benbjurstrom/pgvector-scout.svg?style=flat-square)](https://packagist.org/packages/benbjurstrom/pgvector-scout)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/pgvector-scout.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/pgvector-scout)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require benbjurstrom/pgvector-scout
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="pgvector-scout-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="pgvector-scout-config"
```

This is the contents of the published config file:

```php
return [
    /*
     * The default handler to use for generating embeddings.
     */
    'default' => 'openai',

    'handlers' => [
        'openai' => [
            'class' => \BenBjurstrom\PgvectorScout\Handlers\OpenAiHandler::class,
            'default_model' => 'text-embedding-3-small',
            'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
        ],
    ],
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="pgvector-scout-views"
```


### Installing the pgvector extension
Export environment variables to terminal either throught the UI or by running the following command:

```bash
export PATH=/Users/Shared/DBngin/postgresql/14.3/bin:$PATH
```

```bash
git clone https://github.com/pgvector/pgvector.git
cd pgvector
make && make install
```

## Usage

```php
$pgvectorScout = new BenBjurstrom\PgvectorScout();
echo $pgvectorScout->echoPhrase('Hello, BenBjurstrom!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ben Bjurstrom](https://github.com/benbjurstrom)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
