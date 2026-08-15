<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final readonly class Pollutant
{
    public function __construct(
        public string $key,
        public string $id,
        public string $label,
        public string $unit,
        public int $precision
    ) {
    }
}
