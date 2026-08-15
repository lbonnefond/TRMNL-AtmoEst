<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class StationProvider
{
    public function __construct(
        private readonly AtmoGrandEstClient $client,
        private readonly PollutantRegistry $pollutants =
            new PollutantRegistry()
    ) {
    }

    /**
     * Stations measuring at least one pollutant
     * supported by this plugin.
     *
     * @return list<Station>
     */
    public function all(): array
    {
        $supportedIds = array_map(
            static fn (Pollutant $pollutant): string =>
                $pollutant->id,
            array_values(
                $this->pollutants->all()
            )
        );

        return array_values(
            array_filter(
                $this->client->stations(),
                static function (
                    Station $station
                ) use ($supportedIds): bool {
                    foreach ($supportedIds as $pollutantId) {
                        if (
                            $station->supportsPollutant(
                                $pollutantId
                            )
                        ) {
                            return true;
                        }
                    }

                    return false;
                }
            )
        );
    }

    public function find(
        string $stationId
    ): ?Station {
        foreach ($this->all() as $station) {
            if ($station->id === $stationId) {
                return $station;
            }
        }

        return null;
    }
}