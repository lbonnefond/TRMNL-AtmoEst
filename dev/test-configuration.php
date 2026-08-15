<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\RequestConfigurationFactory;

$factory = new RequestConfigurationFactory();

$config = $factory->create([
    'station' => 'FR16029',
    'pollutant' => 'o3',
]);

printf(
    "Station:   %s\n",
    $config->stationId
);

printf(
    "Pollutant: %s\n",
    $config->pollutantKey
);

printf(
    "Timezone:  %s\n",
    $config->timezone
);

printf(
    "Hours:     %d\n",
    $config->hours
);