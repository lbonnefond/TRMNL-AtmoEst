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
        $primaryPollutant = $this->pollutants->get(
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

        $fetchSince = $graphStart->modify('-2 hours');

        /*
         * Fetch every pollutant supported by the plugin.
         *
         * MeasurementProvider caches the result per station, so one
         * ArcGIS request can feed every graph in the dashboard.
         */
        $pollutantIds = array_map(
            static fn (Pollutant $pollutant): string =>
                $pollutant->id,
            array_values(
                $this->pollutants->all()
            )
        );

        $measurements =
            $this->measurementProvider->measurements(
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

        /*
         * Determine which of the four supported pollutants actually
         * have enough data for this station and graph window.
         *
         * PollutantRegistry::all() already gives us the stable order:
         * PM10, PM2.5, O3, NO2.
         *
         * @var array<string, array{
         *     pollutant: Pollutant,
         *     points: list<array{timestamp: int, value: float}>
         * }> $available
         */
        $available = [];

        foreach ($this->pollutants->all() as $key => $pollutant) {
            $series = $dataset->forPollutant(
                $pollutant->id
            );

            if ($series->count() < 2) {
                continue;
            }

            $points = $this->graphDataGenerator->generate(
                dataset: $dataset,
                pollutantId: $pollutant->id,
                start: $graphStart,
                end: $graphEnd
            );

            if (count($points) < 2) {
                continue;
            }

            $available[$key] = [
                'pollutant' => $pollutant,
                'points' => $points,
            ];
        }

        if (
            !isset(
                $available[$primaryPollutant->key]
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'Not enough measurements for pollutant %s '
                    . 'in the requested graph window.',
                    $primaryPollutant->label
                )
            );
        }

        $primarySeries =
            $dataset->forPollutant(
                $primaryPollutant->id
            );

        $stationName =
            $primarySeries->first()?->stationName
            ?? $configuration->stationId;

        $firstTimestamp =
            $graphStart->getTimestamp();

        $lastTimestamp =
            $graphEnd->getTimestamp();

        $primaryPoints =
            $available[
                $primaryPollutant->key
            ]['points'];

        /*
         * Keep the old single-pollutant output intact.
         *
         * The current TRMNL markup therefore keeps working while the
         * multi-graph markup is developed and tested.
         */
        $imageFull = $this->svgDataUri(
            $this->renderGraph(
                points: $primaryPoints,
                layout: GraphLayout::full(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $imageHalfHorizontal = $this->svgDataUri(
            $this->renderGraph(
                points: $primaryPoints,
                layout: GraphLayout::halfHorizontal(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $imageHalfVertical = $this->svgDataUri(
            $this->renderGraph(
                points: $primaryPoints,
                layout: GraphLayout::halfVertical(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $imageQuadrant = $this->svgDataUri(
            $this->renderGraph(
                points: $primaryPoints,
                layout: GraphLayout::quadrant(),
                timezone: $configuration->timezone,
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp
            )
        );

        $title = $this->pollutantTitle(
            $stationName,
            $primaryPollutant
        );

        $lastMeasurement =
            $primarySeries->last();

        $payload = [
            'title' =>
                $title,

            'station' =>
                $stationName,

            'station_id' =>
                $configuration->stationId,

            'pollutant' =>
                $primaryPollutant->key,

            'pollutant_id' =>
                $primaryPollutant->id,

            'hours' =>
                $configuration->hours,

            'last_measurement' =>
                $lastMeasurement?->start->format(
                    DATE_ATOM
                ),

            'available_pollutant_count' =>
                count($available),

            /*
             * Existing single-graph API.
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

        /*
         * FULL dashboard slots.
         *
         * Stable order:
         * PM10 / PM2.5 / O3 / NO2.
         */
        $fullIndex = 1;

        foreach ($available as $entry) {
            $pollutant =
                $entry['pollutant'];

            $points =
                $entry['points'];

            $prefix =
                'full_graph_' . $fullIndex;

            $payload[$prefix . '_key'] =
                $pollutant->key;

            $payload[$prefix . '_title'] =
                $this->graphTitle(
                    $pollutant
                );

            $payload[$prefix . '_image'] =
                $this->svgDataUri(
                    $this->renderGraph(
                        points: $points,
                        layout: GraphLayout::quadrant(),
                        timezone: $configuration->timezone,
                        firstTimestamp: $firstTimestamp,
                        lastTimestamp: $lastTimestamp
                    )
                );

            $payload[$prefix . '_image_x'] =
                $this->svgDataUri(
                    $this->renderGraph(
                        points: $points,
                        layout: GraphLayout::xLandscapePanel(),
                        timezone: $configuration->timezone,
                        firstTimestamp: $firstTimestamp,
                        lastTimestamp: $lastTimestamp
                    )
                );

            ++$fullIndex;
        }

        /*
         * Two-graph layouts:
         *
         * 1. selected pollutant
         * 2. first other available pollutant in registry order
         */
        $pairEntries = [
            $available[
                $primaryPollutant->key
            ],
        ];

        foreach ($available as $key => $entry) {
            if ($key === $primaryPollutant->key) {
                continue;
            }

            $pairEntries[] = $entry;
            break;
        }

        foreach ($pairEntries as $index => $entry) {
            $slot =
                $index + 1;

            $pollutant =
                $entry['pollutant'];

            $points =
                $entry['points'];

            $prefix =
                'pair_graph_' . $slot;

            $payload[$prefix . '_key'] =
                $pollutant->key;

            $payload[$prefix . '_title'] =
                $this->graphTitle(
                    $pollutant
                );

            /*
             * OG Half Horizontal, Half Vertical and Full 2×2 cells
             * can all use the compact quadrant geometry.
             */
            $payload[$prefix . '_image'] =
                $this->svgDataUri(
                    $this->renderGraph(
                        points: $points,
                        layout: GraphLayout::quadrant(),
                        timezone: $configuration->timezone,
                        firstTimestamp: $firstTimestamp,
                        lastTimestamp: $lastTimestamp
                    )
                );

            /*
             * Dedicated large portrait resource for TRMNL X.
             */
            $payload[$prefix . '_image_x_portrait'] =
                $this->svgDataUri(
                    $this->renderGraph(
                        points: $points,
                        layout: GraphLayout::xPortraitPanel(),
                        timezone: $configuration->timezone,
                        firstTimestamp: $firstTimestamp,
                        lastTimestamp: $lastTimestamp
                    )
                );
        }

        return $payload;
    }

    /**
     * @param list<array{timestamp: int, value: float}> $points
     */
    private function renderGraph(
        array $points,
        GraphLayout $layout,
        string $timezone,
        int $firstTimestamp,
        int $lastTimestamp
    ): string {
        return $this->svgRenderer->render(
            points: $points,
            layout: $layout,
            timezone: $timezone,
            firstTimestamp: $firstTimestamp,
            lastTimestamp: $lastTimestamp
        );
    }

    private function pollutantTitle(
        string $stationName,
        Pollutant $pollutant
    ): string {
        return sprintf(
            '%s — %s (%s)',
            $stationName,
            $pollutant->label,
            $pollutant->unit
        );
    }

    private function graphTitle(
        Pollutant $pollutant
    ): string {
        return sprintf(
            '%s (%s)',
            $pollutant->label,
            $pollutant->unit
        );
    }

    private function svgDataUri(
        string $svg
    ): string {
        return
            'data:image/svg+xml;base64,'
            . base64_encode($svg);
    }
}