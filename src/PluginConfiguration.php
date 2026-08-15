<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final readonly class PluginConfiguration
{
    public function __construct(
        public string $stationId,
        public string $pollutantKey,
        public string $timezone = 'Europe/Paris',
        public int $hours = 24
    ) {
    }
}