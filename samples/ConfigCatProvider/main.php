<?php

declare(strict_types=1);

namespace ConfigCat\Samples;

require __DIR__.'/vendor/autoload.php';

use ConfigCat\ClientOptions;
use ConfigCat\Log\LogLevel;
use ConfigCat\OpenFeature\ConfigCatProvider;
use OpenFeature\OpenFeatureAPI;

// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// build options for the ConfigCatProvider SDK
$options = [
    // Info level logging helps to inspect the feature flag evaluation process.
    // Use the default Warning level to avoid too detailed logging in your application.
    ClientOptions::LOG_LEVEL => LogLevel::INFO,
    ClientOptions::CACHE_REFRESH_INTERVAL => 5,
    // ...
];

// set the OpenFeature provider
$api->setProvider(new ConfigCatProvider('PKDVCLf-Hq-h-kCzMp-L7Q/HhOWfwVtZ0mb30i9wi17GQ', $options));

// retrieve an OpenFeatureClient
$client = $api->getClient();

$isPOCFeatureEnabled = $client->getBooleanValue('isPOCFeatureEnabled', false);
var_dump($isPOCFeatureEnabled);
