<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use InvalidArgumentException;

final class PollutantRegistry
{
    /**
     * @var array<string, Pollutant>
     */
    private array $pollutants;

    public function __construct()
    {
        $this->pollutants = [
            'pm10' => new Pollutant(
                key: 'pm10',
                id: '5',
                label: 'PM10',
                unit: 'µg/m³',
                precision: 0
            ),

            'pm25' => new Pollutant(
                key: 'pm25',
                id: '6001',
                label: 'PM2.5',
                unit: 'µg/m³',
                precision: 0
            ),

            'o3' => new Pollutant(
                key: 'o3',
                id: '7',
                label: 'O₃',
                unit: 'µg/m³',
                precision: 0
            ),

            'no2' => new Pollutant(
                key: 'no2',
                id: '8',
                label: 'NO₂',
                unit: 'µg/m³',
                precision: 0
            ),
        ];
    }

    public function get(string $key): Pollutant
    {
        if (!isset($this->pollutants[$key])) {
            throw new InvalidArgumentException(
                "Unknown pollutant: {$key}"
            );
        }

        return $this->pollutants[$key];
    }

    public function exists(string $key): bool
    {
        return isset($this->pollutants[$key]);
    }

    /**
     * @return array<string, Pollutant>
     */
    public function all(): array
    {
        return $this->pollutants;
    }
}
