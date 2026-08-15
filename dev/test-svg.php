<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\GraphDataGenerator;
use LBonnefond\TrmnlAtmoEst\GraphLayout;
use LBonnefond\TrmnlAtmoEst\MeasurementDataset;
use LBonnefond\TrmnlAtmoEst\SvgGraphRenderer;

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

$layout = GraphLayout::quadrant();

$renderer = new SvgGraphRenderer();

$svg = $renderer->render(
    points: $points,
    layout: $layout,
    timezone: 'Europe/Paris',
    firstTimestamp: $graphStart->getTimestamp(),
    lastTimestamp: $graphEnd->getTimestamp()
);

$outputFile = dirname(__DIR__) . '/dev/o3-quadrant.svg';

file_put_contents(
    $outputFile,
    $svg
);

printf(
    "Graph window: %s -> %s UTC\n",
    $graphStart->format('Y-m-d H:i'),
    $graphEnd->format('Y-m-d H:i')
);

printf(
    "Points: %d\n",
    count($points)
);

printf(
    "SVG written to: %s\n",
    $outputFile
);