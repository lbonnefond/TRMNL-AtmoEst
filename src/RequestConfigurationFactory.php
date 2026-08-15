<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use InvalidArgumentException;

final class RequestConfigurationFactory
{
    public function __construct(
        private readonly PollutantRegistry $pollutants =
            new PollutantRegistry(),
        private readonly ?StationProvider $stations = null
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function create(array $parameters): PluginConfiguration
    {
        $stationId = trim(
            (string)($parameters['station'] ?? '')
        );

        if ($stationId === '') {
            throw new InvalidArgumentException(
                'Missing station parameter.'
            );
        }

        $pollutantKey = strtolower(
            trim(
                (string)($parameters['pollutant'] ?? '')
            )
        );

        if ($pollutantKey === '') {
            throw new InvalidArgumentException(
                'Missing pollutant parameter.'
            );
        }

        if (!$this->pollutants->exists($pollutantKey)) {
            throw new InvalidArgumentException(
                "Unknown pollutant: {$pollutantKey}"
            );
        }

        $pollutant =
            $this->pollutants->get($pollutantKey);

        if ($this->stations !== null) {
            $station =
                $this->stations->find($stationId);

            if ($station === null) {
                throw new InvalidArgumentException(
                    "Unknown station: {$stationId}"
                );
            }

            if (
                !$station->supportsPollutant(
                    $pollutant->id
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Station %s does not measure %s.',
                        $station->name,
                        $pollutant->label
                    )
                );
            }
        }

        return new PluginConfiguration(
            stationId: $stationId,
            pollutantKey: $pollutantKey
        );
    }
}