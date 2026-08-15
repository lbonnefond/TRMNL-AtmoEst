<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\MeasurementCache;
use LBonnefond\TrmnlAtmoEst\MeasurementProvider;

$cacheDirectory =
    dirname(__DIR__)
    . '/var/cache/measurements';

$cache = new MeasurementCache(
    directory: $cacheDirectory,
    ttlSeconds: 600
);

$provider = new MeasurementProvider(
    client: new AtmoGrandEstClient(),
    cache: $cache
);

$now = new DateTimeImmutable(
    'now',
    new DateTimeZone('UTC')
);

$since = $now
    ->setTime(
        (int)$now->format('H'),
        0,
        0
    )
    ->modify('-26 hours');

$pollutantIds = [
    '5',
    '6001',
    '7',
    '8',
];

echo "First request\n";

$start = microtime(true);

$first = $provider->measurements(
    stationId: 'FR16029',
    pollutantIds: $pollutantIds,
    since: $since
);

printf(
    "Measurements: %d\n",
    count($first)
);

printf(
    "Time: %.3f s\n\n",
    microtime(true) - $start
);

echo "Second request\n";

$start = microtime(true);

$second = $provider->measurements(
    stationId: 'FR16029',
    pollutantIds: $pollutantIds,
    since: $since
);

printf(
    "Measurements: %d\n",
    count($second)
);

printf(
    "Time: %.3f s\n",
    microtime(true) - $start
);
