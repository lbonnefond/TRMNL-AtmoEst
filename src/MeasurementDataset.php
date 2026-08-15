<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;
use InvalidArgumentException;

final class MeasurementDataset
{
    /**
     * @var list<Measurement>
     */
    private array $measurements;

    /**
     * @param list<Measurement> $measurements
     */
    public function __construct(array $measurements)
    {
        usort(
            $measurements,
            static function (
                Measurement $a,
                Measurement $b
            ): int {
                $timeComparison =
                    $a->start <=> $b->start;

                if ($timeComparison !== 0) {
                    return $timeComparison;
                }

                return $a->pollutantId <=> $b->pollutantId;
            }
        );

        $this->measurements = array_values($measurements);
    }

    /**
     * @return list<Measurement>
     */
    public function all(): array
    {
        return $this->measurements;
    }

    public function count(): int
    {
        return count($this->measurements);
    }

    public function forPollutant(
        string $pollutantId
    ): self {
        if ($pollutantId === '') {
            throw new InvalidArgumentException(
                'Pollutant ID cannot be empty.'
            );
        }

        return new self(
            array_values(
                array_filter(
                    $this->measurements,
                    static fn (Measurement $measurement): bool =>
                        $measurement->pollutantId === $pollutantId
                )
            )
        );
    }

    public function between(
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): self {
        if ($end <= $start) {
            throw new InvalidArgumentException(
                'End must be later than start.'
            );
        }

        return new self(
            array_values(
                array_filter(
                    $this->measurements,
                    static fn (Measurement $measurement): bool =>
                        $measurement->start >= $start
                        && $measurement->start <= $end
                )
            )
        );
    }

    public function first(): ?Measurement
    {
        return $this->measurements[0] ?? null;
    }

    public function last(): ?Measurement
    {
        if ($this->measurements === []) {
            return null;
        }

        return $this->measurements[
            array_key_last($this->measurements)
        ];
    }
}
