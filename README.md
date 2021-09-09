# Guzzle helpers

Guzzle helpers. Add Header and Json obfuscators for guzzle middleware.
Used for remove autorization headers and sensitive date from response logs.

## Requirements

* Guzzle >=7
* PHP version >=7.4.1
* Docker (optional)

## Installation

In your `composer.json`, add this repository:
```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/tenantcloud/guzzle-helpers"
    }
],
```
Then do `composer require tenantcloud/guzzle-helpers` to install the package.

### Commands
Install dependencies:
`docker run -it --rm -v $PWD:/app -w /app composer install`

Run tests:
`docker run -it --rm -v $PWD:/app -w /app php:7.4-cli vendor/bin/phpunit`

Run php-cs-fixer on self:
`docker run -it --rm -v $PWD:/app -w /app composer cs-fix`
