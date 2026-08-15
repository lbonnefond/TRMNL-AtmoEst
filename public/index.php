<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\PluginController;
use LBonnefond\TrmnlAtmoEst\RequestConfigurationFactory;
use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\MeasurementCache;
use LBonnefond\TrmnlAtmoEst\MeasurementProvider;
use LBonnefond\TrmnlAtmoEst\StationProvider;


header('Content-Type: application/json; charset=utf-8');

try {
    $stationProvider = new StationProvider(
        client: new AtmoGrandEstClient()
    );

    $factory = new RequestConfigurationFactory(
        stations: $stationProvider
    );

    $configuration = $factory->create(
        $_GET
    );

    $cache = new MeasurementCache(
        directory: dirname(__DIR__)
            . '/var/cache/measurements',
        ttlSeconds: 600
    );

    $provider = new MeasurementProvider(
        client: new AtmoGrandEstClient(),
        cache: $cache
    );

    $controller = new PluginController(
        measurementProvider: $provider
    );

    $payload = $controller->render(
        $configuration
    );

    echo json_encode(
        $payload,
        JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode(
        [
            'message' => $exception->getMessage(),
        ],
        JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
}
