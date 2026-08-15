<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;
use InvalidArgumentException;

final class GraphDataGenerator
{
    /**
     * @return list<array{timestamp: int, value: float}>
     */
    public function generate(
        MeasurementDataset $dataset,
        string $pollutantId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        if ($end <= $start) {
            throw new InvalidArgumentException(
                'Graph end must be later than graph start.'
            );
        }

        $measurements = $dataset
            ->forPollutant($pollutantId)
            ->between($start, $end)
            ->all();

        return array_map(
            static fn (Measurement $measurement): array => [
                'timestamp' => $measurement->start->getTimestamp(),
                'value' => $measurement->value,
            ],
            $measurements
        );
    }
}