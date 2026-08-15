<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;

final class MeasurementProvider
{
    public function __construct(
        private readonly AtmoGrandEstClient $client,
        private readonly MeasurementCache $cache
    ) {
    }

    /**
     * @param list<string> $pollutantIds
     * @return list<Measurement>
     */
    public function measurements(
        string $stationId,
        array $pollutantIds,
        DateTimeImmutable $since
    ): array {
        $cached = $this->cache->get(
            $stationId
        );

        if ($cached !== null) {
            return $this->filter(
                $cached,
                $pollutantIds,
                $since
            );
        }

        $measurements = $this->client->measurements(
            $stationId,
            $pollutantIds,
            $since
        );

        $this->cache->put(
            $stationId,
            $measurements
        );

        return $measurements;
    }

    /**
     * @param list<Measurement> $measurements
     * @param list<string> $pollutantIds
     * @return list<Measurement>
     */
    private function filter(
        array $measurements,
        array $pollutantIds,
        DateTimeImmutable $since
    ): array {
        $allowed = array_fill_keys(
            $pollutantIds,
            true
        );

        return array_values(
            array_filter(
                $measurements,
                static fn (Measurement $measurement): bool =>
                    isset(
                        $allowed[$measurement->pollutantId]
                    )
                    && $measurement->start >= $since
            )
        );
    }
}