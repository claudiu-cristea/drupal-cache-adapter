[![ci](https://github.com/claudiu-cristea/drupal-cache-adapter/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/claudiu-cristea/drupal-cache-adapter/actions/workflows/ci.yml)

## Drupal Cache Adapter

Provides a Symfony Cache adapter to Drupal cache system.

It's  useful when a third-party library requires a `php-cache` style adapter to
cache data but you want to pipe the cachig process through the Drupal cache API.

A good example is https://github.com/KnpLabs/php-github-api, a library that is
querying the GitHub API. Calls to GitHub might be cached but the library
requires a `php-cache` adapter. You can use the `DrupalAdapter` provided by this
package, to route the cache write/read via Drupal caching API. See
https://github.com/KnpLabs/php-github-api/blob/master/doc/caching.md.

## Install

Use composer:

```shell
composer require claudiu-cristea/drupal-cache-adapter
```

## Usage

```php
<?php

use Drupal\Cache\Adapter\DrupalAdapter;
use ThirdParty\Library\Client;

class SomeService {

  public function doSomething() {
    ...
    $client = new Client(...);
    $adapter = new DrupalAdapter(\Drupal::service('cache.data'), 'some-prefix');
    $client->addCacheBackend($adapter);
    $client->fetch();
    ...
  }

}
```
