<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\MeasurementCache;
use LBonnefond\TrmnlAtmoEst\MeasurementProvider;
use LBonnefond\TrmnlAtmoEst\PluginController;
use LBonnefond\TrmnlAtmoEst\RequestConfigurationFactory;


$factory =
    new RequestConfigurationFactory();

$configuration =
    $factory->create([
        'station' => 'FR16029',
        'pollutant' => 'o3',
    ]);

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

$payload =
    $controller->render(
        $configuration
    );

printf(
    "Title:            %s\n",
    $payload['title']
);

printf(
    "Station ID:       %s\n",
    $payload['station_id']
);

printf(
    "Pollutant:        %s\n",
    $payload['pollutant']
);

printf(
    "Last measurement: %s\n",
    $payload['last_measurement']
        ?? 'none'
);

printf(
    "Full image:       %d bytes\n",
    strlen($payload['image_full'])
);

printf(
    "Half horizontal:  %d bytes\n",
    strlen(
        $payload['image_half_horizontal']
    )
);

printf(
    "Half vertical:    %d bytes\n",
    strlen(
        $payload['image_half_vertical']
    )
);

printf(
    "Quadrant:         %d bytes\n",
    strlen(
        $payload['image_quadrant']
    )
);
