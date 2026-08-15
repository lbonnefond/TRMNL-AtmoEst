<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class AtmoGrandEstClient
{
    private const QUERY_URL =
    'https://services3.arcgis.com/Is0UwT37raQYl9Jj/ArcGIS/rest/services/'
        . 'mes_Grand_Est_horaire_poll_princ/FeatureServer/0/query';

    private const PAGE_SIZE = 1000;

    /**
     * @param list<string> $pollutantIds
     *
     * @return list<Measurement>
     */
    public function measurements(
        string $stationId,
        array $pollutantIds,
        DateTimeImmutable $since
    ): array {
        if ($stationId === '') {
            throw new RuntimeException('Station ID cannot be empty.');
        }

        if ($pollutantIds === []) {
            return [];
        }

        $sinceUtc = $since->setTimezone(new DateTimeZone('UTC'));

        $pollutantsSql = implode(
            ',',
            array_map(
                static fn(string $id): string =>
                "'" . str_replace("'", "''", $id) . "'",
                $pollutantIds
            )
        );

        $stationSql = str_replace("'", "''", $stationId);

        $where = sprintf(
            "code_station_ue='%s' "
                . 'AND id_poll_ue IN (%s) '
                . "AND date_debut >= TIMESTAMP '%s'",
            $stationSql,
            $pollutantsSql,
            $sinceUtc->format('Y-m-d H:i:s')
        );

        $measurements = [];
        $offset = 0;

        do {
            $response = $this->query([
                'where' => $where,
                'outFields' => implode(',', [
                    'code_station_ue',
                    'nom_station',
                    'id_poll_ue',
                    'nom_poll',
                    'date_debut',
                    'date_fin',
                    'valeur',
                    'unite',
                    'statut_valid',
                ]),
                'returnGeometry' => 'false',
                'orderByFields' => 'date_debut ASC,id_poll_ue ASC',
                'resultOffset' => (string) $offset,
                'resultRecordCount' => (string) self::PAGE_SIZE,
                'f' => 'json',
            ]);

            $features = $response['features'] ?? [];

            if (!is_array($features)) {
                throw new RuntimeException(
                    'Invalid ArcGIS response: features is not an array.'
                );
            }

            foreach ($features as $feature) {
                if (!is_array($feature)) {
                    continue;
                }

                $attributes = $feature['attributes'] ?? null;

                if (!is_array($attributes)) {
                    continue;
                }

                $measurements[] = $this->measurementFromAttributes(
                    $attributes
                );
            }

            $offset += count($features);

            $hasMore = ($response['exceededTransferLimit'] ?? false) === true;
        } while ($hasMore && $features !== []);

        return $measurements;
    }

    /**
     * @return list<Station>
     */
    public function stations(): array
    {
        $response = $this->query([
            'where' => '1=1',
            'outFields' => implode(',', [
                'code_station_ue',
                'nom_station',
                'nom_com',
                'nom_dept',
                'typologie',
                'influence',
                'id_poll_ue',
            ]),
            'returnGeometry' => 'false',
            'returnDistinctValues' => 'true',
            'orderByFields' =>
            'nom_com,nom_station,id_poll_ue',
            'f' => 'json',
        ]);

        $features = $response['features'] ?? [];

        if (!is_array($features)) {
            throw new RuntimeException(
                'Invalid ArcGIS response: features is not an array.'
            );
        }

        /**
         * @var array<string, array{
         *     id: string,
         *     name: string,
         *     city: string,
         *     department: string,
         *     typology: string,
         *     influence: string,
         *     pollutantIds: list<string>
         * }> $stations
         */
        $stations = [];

        foreach ($features as $feature) {
            if (!is_array($feature)) {
                continue;
            }

            $attributes = $feature['attributes'] ?? null;

            if (!is_array($attributes)) {
                continue;
            }

            $stationId =
                (string)$attributes['code_station_ue'];

            if (!isset($stations[$stationId])) {
                $stations[$stationId] = [
                    'id' => $stationId,
                    'name' =>
                    (string)$attributes['nom_station'],
                    'city' =>
                    (string)$attributes['nom_com'],
                    'department' =>
                    (string)$attributes['nom_dept'],
                    'typology' =>
                    (string)$attributes['typologie'],
                    'influence' =>
                    (string)$attributes['influence'],
                    'pollutantIds' => [],
                ];
            }

            $pollutantId =
                (string)$attributes['id_poll_ue'];

            if (
                !in_array(
                    $pollutantId,
                    $stations[$stationId]['pollutantIds'],
                    true
                )
            ) {
                $stations[$stationId]['pollutantIds'][] =
                    $pollutantId;
            }
        }

        $result = [];

        foreach ($stations as $station) {
            sort(
                $station['pollutantIds'],
                SORT_STRING
            );

            $result[] = new Station(
                id: $station['id'],
                name: $station['name'],
                city: $station['city'],
                department: $station['department'],
                typology: $station['typology'],
                influence: $station['influence'],
                pollutantIds: $station['pollutantIds']
            );
        }

        usort(
            $result,
            static function (
                Station $a,
                Station $b
            ): int {
                $cityComparison =
                    strcasecmp(
                        $a->city,
                        $b->city
                    );

                if ($cityComparison !== 0) {
                    return $cityComparison;
                }

                return strcasecmp(
                    $a->name,
                    $b->name
                );
            }
        );

        return $result;
    }
    /**
     * @param array<string, scalar> $parameters
     *
     * @return array<string, mixed>
     */
    private function query(array $parameters): array
    {
        $url = self::QUERY_URL . '?' . http_build_query(
            $parameters,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'TRMNL-AtmoEst/1.0',
        ]);

        $body = curl_exec($curl);

        if ($body === false) {
            $error = curl_error($curl);

            throw new RuntimeException(
                'ArcGIS request failed: ' . $error
            );
        }

        $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);


        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new RuntimeException(
                "ArcGIS request returned HTTP {$httpStatus}."
            );
        }

        try {
            $data = json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                'Invalid JSON returned by ArcGIS.',
                0,
                $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid ArcGIS response.'
            );
        }

        if (isset($data['error'])) {
            $message = 'Unknown ArcGIS error';

            if (
                is_array($data['error'])
                && isset($data['error']['message'])
                && is_string($data['error']['message'])
            ) {
                $message = $data['error']['message'];
            }

            throw new RuntimeException(
                'ArcGIS error: ' . $message
            );
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function measurementFromAttributes(
        array $attributes
    ): Measurement {
        return new Measurement(
            stationId: (string) $attributes['code_station_ue'],
            stationName: (string) $attributes['nom_station'],
            pollutantId: (string) $attributes['id_poll_ue'],
            pollutantName: (string) $attributes['nom_poll'],
            start: $this->dateFromArcGisTimestamp(
                $attributes['date_debut']
            ),
            end: $this->dateFromArcGisTimestamp(
                $attributes['date_fin']
            ),
            value: (float) $attributes['valeur'],
            unit: (string) $attributes['unite'],
            validationStatus: (int) $attributes['statut_valid']
        );
    }

    private function dateFromArcGisTimestamp(
        mixed $timestampMilliseconds
    ): DateTimeImmutable {
        if (
            !is_int($timestampMilliseconds)
            && !is_float($timestampMilliseconds)
        ) {
            throw new RuntimeException(
                'Invalid ArcGIS timestamp.'
            );
        }

        $timestampSeconds = (int) floor(
            $timestampMilliseconds / 1000
        );

        return (new DateTimeImmutable(
            '@' . $timestampSeconds
        ))->setTimezone(
            new DateTimeZone('UTC')
        );
    }
}
