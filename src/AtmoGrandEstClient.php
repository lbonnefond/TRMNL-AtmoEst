<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class AtmoGrandEstClient
{
    private const MEASUREMENTS_URL =
    'https://www.atmo-grandest.eu/dataviz/dataviz/mesures';

    private const STATIONS_URL =
    'https://services3.arcgis.com/Is0UwT37raQYl9Jj/ArcGIS/rest/services/'
        . 'mes_Grand_Est_horaire_poll_princ/FeatureServer/0/query';

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
            throw new RuntimeException(
                'Station ID cannot be empty.'
            );
        }

        if ($pollutantIds === []) {
            return [];
        }

        $timezone = new DateTimeZone('Europe/Paris');

        $start = $since->setTimezone($timezone);

        /*
         * The Atmo Grand Est dataviz API works with calendar dates.
         *
         * We request from the calendar day containing $since through
         * the current calendar day, then filter the returned points
         * against the exact $since timestamp.
         */
        $startDate = $start->format('Y-m-d');

        $endDate = (new DateTimeImmutable(
            'now',
            $timezone
        ))->format('Y-m-d');

        $measurements = [];

        $stationName = $this->stationName($stationId);

        foreach ($pollutantIds as $pollutantId) {
            $url = sprintf(
                '%s/%s/%s/%s/%s',
                self::MEASUREMENTS_URL,
                rawurlencode($stationId),
                rawurlencode($pollutantId),
                $startDate,
                $endDate
            );

            $response = $this->requestJson($url);

            foreach (
                $this->extractMeasurements(
                    $response,
                    $stationId,
                    $stationName,
                    $pollutantId
                ) as $measurement
            ) {
                if ($measurement->start >= $since) {
                    $measurements[] = $measurement;
                }
            }
        }

        usort(
            $measurements,
            static function (
                Measurement $a,
                Measurement $b
            ): int {
                $comparison = $a->start <=> $b->start;

                if ($comparison !== 0) {
                    return $comparison;
                }

                return strcmp(
                    $a->pollutantId,
                    $b->pollutantId
                );
            }
        );

        return $measurements;
    }

    /**
     * @return list<Station>
     */
    public function stations(): array
    {
        $response = $this->requestJson(
            self::STATIONS_URL
                . '?'
                . http_build_query(
                    [
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
                    ],
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                )
        );

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
                (string) ($attributes['code_station_ue'] ?? '');

            if ($stationId === '') {
                continue;
            }

            if (!isset($stations[$stationId])) {
                $stations[$stationId] = [
                    'id' => $stationId,
                    'name' =>
                    (string) (
                        $attributes['nom_station'] ?? ''
                    ),
                    'city' =>
                    (string) (
                        $attributes['nom_com'] ?? ''
                    ),
                    'department' =>
                    (string) (
                        $attributes['nom_dept'] ?? ''
                    ),
                    'typology' =>
                    (string) (
                        $attributes['typologie'] ?? ''
                    ),
                    'influence' =>
                    (string) (
                        $attributes['influence'] ?? ''
                    ),
                    'pollutantIds' => [],
                ];
            }

            $pollutantId =
                (string) (
                    $attributes['id_poll_ue'] ?? ''
                );

            if (
                $pollutantId !== ''
                && !in_array(
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
     * @return array<int|string, mixed>
     */
    private function requestJson(
        string $url
    ): array {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException(
                'Unable to initialize cURL.'
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'TRMNL-AtmoEst/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($curl);

        if ($body === false) {
            $error = curl_error($curl);

            curl_close($curl);

            throw new RuntimeException(
                'Atmo Grand Est request failed: '
                    . $error
            );
        }

        $status = curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                sprintf(
                    'Atmo Grand Est returned HTTP %d.',
                    $status
                )
            );
        }

        $data = json_decode(
            $body,
            true
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid JSON returned by Atmo Grand Est.'
            );
        }

        return $data;
    }

    private function stationName(
        string $stationId
    ): string {
        $response = $this->requestJson(
            self::STATIONS_URL
                . '?'
                . http_build_query(
                    [
                        'where' =>
                        "code_station_ue='"
                            . str_replace("'", "''", $stationId)
                            . "'",
                        'outFields' => 'nom_station',
                        'returnGeometry' => 'false',
                        'resultRecordCount' => '1',
                        'f' => 'json',
                    ],
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                )
        );

        $features = $response['features'] ?? [];

        if (
            !is_array($features)
            || $features === []
            || !is_array($features[0] ?? null)
        ) {
            return $stationId;
        }

        $attributes = $features[0]['attributes'] ?? [];

        if (!is_array($attributes)) {
            return $stationId;
        }

        $name = (string) ($attributes['nom_station'] ?? '');

        return $name !== '' ? $name : $stationId;
    }

    /**
     * Convert the current Atmo dataviz response into Measurement
     * objects.
     *
     * The endpoint returns a plain JSON array:
     *
     * [
     *     {
     *         "date": 1787698800000,
     *         "pollutant": 19,
     *         "formatedPollutant": "19",
     *         "legendValidation": "validée",
     *         ...
     *     }
     * ]
     *
     * @param array<int|string, mixed> $response
     * @return list<Measurement>
     */
    private function extractMeasurements(
        array $response,
        string $stationId,
        string $stationName,
        string $pollutantId
    ): array {
        $result = [];

        foreach ($response as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (
                !isset($row['date'])
                || !is_numeric($row['date'])
            ) {
                continue;
            }

            if (
                !isset($row['pollutant'])
                || !is_numeric($row['pollutant'])
            ) {
                continue;
            }

            /*
             * The API timestamp is in milliseconds.
             */
            $timestamp = intdiv(
                (int) $row['date'],
                1000
            );

            $start = (new DateTimeImmutable(
                '@' . $timestamp
            ))->setTimezone(
                new DateTimeZone('UTC')
            );

            $value = (float) $row['pollutant'];

            /*
             * legendValidation is "validée" or "non validée".
             *
             * Measurement currently stores an integer validation
             * status, so use 0 for validated and 1 for non-validated.
             */
            $validationStatus =
                (
                    ($row['legendValidation'] ?? '')
                    === 'non validée'
                )
                ? 1
                : 0;

            $result[] = new Measurement(
                stationId: $stationId,
                stationName: $stationName,
                pollutantId: $pollutantId,
                pollutantName: '',
                start: $start,
                end: $start->modify('+1 hour'),
                value: $value,
                unit: 'µg/m³',
                validationStatus: $validationStatus
            );
        }

        return $result;
    }
}
