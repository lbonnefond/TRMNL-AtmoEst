<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\PollutantRegistry;
use LBonnefond\TrmnlAtmoEst\StationProvider;

$pollutants = new PollutantRegistry();

$provider = new StationProvider(
    client: new AtmoGrandEstClient(),
    pollutants: $pollutants
);

$stations = $provider->all();

printf(
    "Eligible stations: %d\n\n",
    count($stations)
);

foreach ($stations as $station) {
    $labels = [];

    foreach ($pollutants->all() as $pollutant) {
        if (
            $station->supportsPollutant(
                $pollutant->id
            )
        ) {
            $labels[] = $pollutant->label;
        }
    }

    printf(
        "%-8s | %-45s | %s\n",
        $station->id,
        $station->label(),
        implode(', ', $labels)
    );
}