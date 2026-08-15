<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\GraphDataGenerator;
use LBonnefond\TrmnlAtmoEst\GraphLayout;
use LBonnefond\TrmnlAtmoEst\MeasurementDataset;
use LBonnefond\TrmnlAtmoEst\PollutantRegistry;
use LBonnefond\TrmnlAtmoEst\SvgGraphRenderer;
use LBonnefond\TrmnlAtmoEst\Trmnl\HtmlRenderer;

$timezone = 'Europe/Paris';

$client = new AtmoGrandEstClient();
$pollutants = new PollutantRegistry();

$pollutant = $pollutants->get('o3');

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

$series = $dataset->forPollutant(
    $pollutant->id
);

$generator = new GraphDataGenerator();

$points = $generator->generate(
    $dataset,
    $pollutant->id,
    $graphStart,
    $graphEnd
);

$layout = GraphLayout::quadrant();

$svgRenderer = new SvgGraphRenderer();

$svg = $svgRenderer->render(
    points: $points,
    layout: $layout,
    timezone: $timezone,
    firstTimestamp: $graphStart->getTimestamp(),
    lastTimestamp: $graphEnd->getTimestamp()
);

$lastMeasurement = $series->last();

$htmlRenderer = new HtmlRenderer();

$html = $htmlRenderer->render(
    svg: $svg,
    layout: $layout,
    stationName: 'Strasbourg Nord',
    pollutant: $pollutant,
    lastMeasurement: $lastMeasurement?->start,
    timezone: $timezone
);

$outputFile =
    dirname(__DIR__) . '/dev/o3-quadrant.html';

file_put_contents(
    $outputFile,
    $html
);

printf(
    "Measurements: %d\n",
    count($points)
);

printf(
    "Last measurement: %s UTC\n",
    $lastMeasurement?->start->format('Y-m-d H:i')
        ?? 'none'
);

printf(
    "HTML written to: %s\n",
    $outputFile
);