# ConfigCat OpenFeature Provider for PHP

[![Build Status](https://github.com/configcat/php-openfeature/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/configcat/php-openfeature/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/configcat/openfeature-provider/version)](https://packagist.org/packages/configcat/openfeature-provider)
[![Total Downloads](https://poser.pugx.org/configcat/openfeature-provider/downloads)](https://packagist.org/packages/configcat/openfeature-provider)

This repository contains an OpenFeature provider that allows the usage of [ConfigCat](https://configcat.com) with the [OpenFeature PHP SDK](https://github.com/open-feature/php-sdk).

## Installation

```sh
composer require configcat/openfeature-provider
```

## Usage

The `ConfigCatProvider` client constructor takes the SDK key and an optional `array` argument containing the additional configuration options for the [ConfigCat PHP SDK](https://github.com/configcat/php-sdk):

```php
// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// build options for the ConfigCatProvider SDK
$options = [
  ClientOptions::LOG_LEVEL => LogLevel::WARNING,
  ClientOptions::LOGGER => new \Monolog\Logger("name"),
  ClientOptions::CACHE => new \ConfigCat\Cache\LaravelCache(Cache::store()),
  ClientOptions::CACHE_REFRESH_INTERVAL => 5,
  //...
];

// set the OpenFeature provider
$api->setProvider(new ConfigCatProvider('<YOUR-CONFIGCAT-SDK-KEY>', $options));

// retrieve an OpenFeatureClient
$client = $api->getClient();

$isMyAwesomeFeatureEnabled = $client->getBooleanValue('isMyAwesomeFeatureEnabled', false);
```

For more information about all the configuration options, see the [PHP SDK documentation](https://configcat.com/docs/sdk-reference/php/#creating-the-configcat-client).

## Need help?
https://configcat.com/support

## Contributing
Contributions are welcome. For more info please read the [Contribution Guideline](CONTRIBUTING.md).

## About ConfigCat
ConfigCat is a feature flag and configuration management service that lets you separate releases from deployments. You can turn your features ON/OFF using <a href="https://app.configcat.com" target="_blank">ConfigCat Dashboard</a> even after they are deployed. ConfigCat lets you target specific groups of users based on region, email or any other custom user attribute.

ConfigCat is a <a href="https://configcat.com" target="_blank">hosted feature flag service</a>. Manage feature toggles across frontend, backend, mobile, desktop apps. <a href="https://configcat.com" target="_blank">Alternative to LaunchDarkly</a>. Management app + feature flag SDKs.

- [Official ConfigCat SDKs for other platforms](https://github.com/configcat)
- [Documentation](https://configcat.com/docs)
- [Blog](https://configcat.com/blog)
