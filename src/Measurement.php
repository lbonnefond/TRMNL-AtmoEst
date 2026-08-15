<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;

final readonly class Measurement
{
    public function __construct(
        public string $stationId,
        public string $stationName,
        public string $pollutantId,
        public string $pollutantName,
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public float $value,
        public string $unit,
        public int $validationStatus
    ) {
    }

    public function isCurrent(DateTimeImmutable $now): bool
    {
        return $now >= $this->start && $now < $this->end;
    }

    public function isValidated(): bool
    {
        return $this->validationStatus === 1;
    }
}
