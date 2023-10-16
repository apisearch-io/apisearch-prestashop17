# PrestaShop module library for Guzzle clients

Plug modules to the Guzzle client available on a running shop.
This library is compatible with PHP 5.6.0 and above. Please check [Version Guidance](#version-guidance) for the PHP version compatibility.

[![Latest Stable Version](https://img.shields.io/packagist/v/prestashop/module-lib-guzzle-adapter.svg?style=flat-square)](https://packagist.org/packages/prestashop/module-lib-guzzle-adapter) [![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6.0-8892BF.svg?style=flat-square)](https://php.net/) [![Quality Control Status](https://img.shields.io/github/workflow/status/prestashopcorp/module-lib-guzzle-adapter/PHP%20tests?style=flat-square)](https://github.com/PrestaShopCorp/module-lib-guzzle-adapter/actions/workflows/php.yml)

## Installation

```
composer require prestashop/module-lib-guzzle-adapter
```

## Version Guidance

| Version | Status         | Packagist           -| Namespace    | Repo                | Docs                | PHP Version  |
|---------|----------------|----------------------|--------------|---------------------|---------------------|--------------|
| <=0.5  | Stable         | `prestashop/module-lib-guzzle-adapter` | `Prestashop\ModuleLibGuzzleAdapter` | [v0.x][lib-1-repo] | N/A                 | >=7.2.5   |
| >=0.6     | Latest         | `prestashop/module-lib-guzzle-adapter` | `Prestashop\ModuleLibGuzzleAdapter` | [v0.x][lib-php5-repo] | N/A                 | >=5.6.0   |
| 1.x     | Latest         | `prestashop/module-lib-guzzle-adapter` | `Prestashop\ModuleLibGuzzleAdapter` | [v1.x][lib-1-repo] | N/A                 | >=7.2.5   |

[lib-1-repo]: https://github.com/PrestaShopCorp/module-lib-guzzle-adapter/tree/main
[lib-php5-repo]: https://github.com/PrestaShopCorp/module-lib-guzzle-adapter/tree/0.x

## Usage

```php
# Getting a client (Psr\Http\Client\ClientInterface)
$options = ['base_url' => 'http://some-url/'];
$client = (new Prestashop\ModuleLibGuzzleAdapter\ClientFactory())->getClient($options);

# Sending requests and receive response (Psr\Http\Message\ResponseInterface)
$response = $this->client->sendRequest(
    new GuzzleHttp\Psr7\Request('POST', 'some-uri')
);
```

In this example, `base_url` is known to be a option for Guzzle 5 that has been replaced for `base_uri` on Guzzle 6+. Any of this two keys can be set, as it will be automatically modified for the other client if needed.

The automatically changed properties are:

| Guzzle 5 property | | Guzzle 7 property |
| ------------- | -- | ------------- |
| base_url  | <=> | base_url  |
| defaults.authorization | <=> | authorization  |
| defaults.exceptions | <=> | http_errors  |
| defaults.timeout | <=> | timeout  |

## Why this library?

Making HTTP requests in a PrestaShop module can be done in several ways. With `file_get_contents()`, cURL or Guzzle when provided by the core.

Depending on the running version of PrestaShop, the bundled version of Guzzle can be different:
* PrestaShop 1.7: Guzzle 5
* PrestaShop 8: Guzzle 7

Having a module compatible for these two major PrestaShop versions can be tricky. The classes provided by the two Guzzle version are named the same, but their methods are different.

It is not possible for a module contributor to require its own Guzzle dependency either, because PHP cannot load different versions of a same class and he would never know which one would be loaded first.

## Implementation notes

This library reuses the idea behind [PHP-HTTP](https://docs.php-http.org), where the implementation of HTTP requests should be the same (PSR) whatever the client chosen.

The client files from [php-http/guzzle5-adapter](https://github.com/php-http/guzzle5-adapter) and [php-http/guzzle7-adapter](https://github.com/php-http/guzzle7-adapter) have been copied in this repository because these libraries both require a different version Guzzle in their dependencies to work. Requiring them together would conflict, so we duplicated the client adapters to be safe.
