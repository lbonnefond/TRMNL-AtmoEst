<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\GraphDataGenerator;
use LBonnefond\TrmnlAtmoEst\MeasurementDataset;

$client = new AtmoGrandEstClient();

$now = new DateTimeImmutable(
    'now',
    new DateTimeZone('UTC')
);

$graphEnd = $now->setTime(
    (int) $now->format('H'),
    0,
    0
);

$graphStart = $graphEnd->modify('-24 hours');

/*
 * Fetch slightly more data than the graph needs.
 */
$fetchSince = $graphEnd->modify('-26 hours');

$measurements = $client->measurements(
    'FR16029',
    ['5', '6001', '7', '8'],
    $fetchSince
);

$dataset = new MeasurementDataset($measurements);

$generator = new GraphDataGenerator();

$points = $generator->generate(
    $dataset,
    '7',
    $graphStart,
    $graphEnd
);

printf(
    "Now:         %s\n",
    $now->format('Y-m-d H:i:s T')
);

printf(
    "Fetch since: %s\n",
    $fetchSince->format('Y-m-d H:i:s T')
);

printf(
    "Graph start: %s\n",
    $graphStart->format('Y-m-d H:i:s T')
);

printf(
    "Graph end:   %s\n",
    $graphEnd->format('Y-m-d H:i:s T')
);

printf(
    "Dataset:     %d measurements\n",
    $dataset->count()
);

printf(
    "O3 points:   %d\n\n",
    count($points)
);

foreach ($points as $point) {
    printf(
        "%s  %6.1f\n",
        (new DateTimeImmutable('@' . $point['timestamp']))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i'),
        $point['value']
    );
}