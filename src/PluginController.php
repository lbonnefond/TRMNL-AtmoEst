<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class PluginController
{
    public function __construct(
        private readonly MeasurementProvider $measurementProvider,
        private readonly PollutantRegistry $pollutants =
            new PollutantRegistry(),
        private readonly GraphDataGenerator $graphDataGenerator =
            new GraphDataGenerator(),
        private readonly SvgGraphRenderer $svgRenderer =
            new SvgGraphRenderer()
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function render(
        PluginConfiguration $configuration
    ): array {
        $pollutant = $this->pollutants->get(
            $configuration->pollutantKey
        );

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $graphEnd = $now->setTime(
            (int)$now->format('H'),
            0,
            0
        );

        $graphStart = $graphEnd->modify(
            sprintf(
                '-%d hours',
                $configuration->hours
            )
        );

        /*
         * Retrieve a small safety margin beyond the graph window.
         */
        $fetchSince = $graphStart->modify('-2 hours');

        /*
         * Fetch all supported pollutants for the station.
         *
         * This prepares the controller for station-level caching later:
         * one ArcGIS request can feed several plugin instances.
         */
        $pollutantIds = array_map(
            static fn (Pollutant $item): string =>
                $item->id,
            array_values(
                $this->pollutants->all()
            )
        );

        $measurements = $this->measurementProvider->measurements(
            $configuration->stationId,
            $pollutantIds,
            $fetchSince
        );

        if ($measurements === []) {
            throw new RuntimeException(
                'No measurements returned by Atmo Grand Est.'
            );
        }

        $dataset = new MeasurementDataset(
            $measurements
        );

        $series = $dataset->forPollutant(
            $pollutant->id
        );

        if ($series->count() < 2) {
            throw new RuntimeException(
                sprintf(
                    'Not enough measurements for pollutant %s.',
                    $pollutant->label
                )
            );
        }

        $points = $this->graphDataGenerator->generate(
            dataset: $dataset,
            pollutantId: $pollutant->id,
            start: $graphStart,
            end: $graphEnd
        );

        if (count($points) < 2) {
            throw new RuntimeException(
                sprintf(
                    'Not enough measurements for pollutant %s '
                    . 'in the requested graph window.',
                    $pollutant->label
                )
            );
        }

        $stationName =
            $series->first()?->stationName
            ?? $configuration->stationId;

        $title = sprintf(
            '%s — %s (%s)',
            $stationName,
            $pollutant->label,
            $pollutant->unit
        );

        $firstTimestamp =
            $graphStart->getTimestamp();

        $lastTimestamp =
            $graphEnd->getTimestamp();

        $imageFull = $this->svgDataUri(
            $this->svgRenderer->render(
                points: $points,
                layout: GraphLayout::full(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $imageHalfHorizontal = $this->svgDataUri(
            $this->svgRenderer->render(
                points: $points,
                layout: GraphLayout::halfHorizontal(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $imageHalfVertical = $this->svgDataUri(
            $this->svgRenderer->render(
                points: $points,
                layout: GraphLayout::halfVertical(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $imageQuadrant = $this->svgDataUri(
            $this->svgRenderer->render(
                points: $points,
                layout: GraphLayout::quadrant(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $lastMeasurement =
            $series->last();

        return [
            'title' =>
                $title,

            'station' =>
                $stationName,

            'station_id' =>
                $configuration->stationId,

            'pollutant' =>
                $pollutant->key,

            'pollutant_id' =>
                $pollutant->id,

            'hours' =>
                $configuration->hours,

            'last_measurement' =>
                $lastMeasurement?->start->format(
                    DATE_ATOM
                ),

            /*
             * Generic fallback used by the Shared TRMNL markup.
             */
            'image' =>
                $imageFull,

            'image_full' =>
                $imageFull,

            'image_half_horizontal' =>
                $imageHalfHorizontal,

            'image_half_vertical' =>
                $imageHalfVertical,

            'image_quadrant' =>
                $imageQuadrant,
        ];
    }

    private function svgDataUri(
        string $svg
    ): string {
        return
            'data:image/svg+xml;base64,'
            . base64_encode($svg);
    }
}