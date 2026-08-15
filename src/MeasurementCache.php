<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

use DateTimeImmutable;
use RuntimeException;

final class MeasurementCache
{
    public function __construct(
        private readonly string $directory,
        private readonly int $ttlSeconds = 600
    ) {
        if ($this->ttlSeconds < 0) {
            throw new RuntimeException(
                'Cache TTL cannot be negative.'
            );
        }
    }

    /**
     * @return list<Measurement>|null
     */
    public function get(string $stationId): ?array
    {
        $filename = $this->filename($stationId);

        if (!is_file($filename)) {
            return null;
        }

        $mtime = filemtime($filename);

        if ($mtime === false) {
            return null;
        }

        if ((time() - $mtime) > $this->ttlSeconds) {
            return null;
        }

        $json = file_get_contents($filename);

        if ($json === false) {
            return null;
        }

        try {
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $measurements = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                return null;
            }

            try {
                $measurements[] = new Measurement(
                    stationId: (string)$item['station_id'],
                    stationName: (string)$item['station_name'],
                    pollutantId: (string)$item['pollutant_id'],
                    pollutantName: (string)$item['pollutant_name'],
                    start: new DateTimeImmutable(
                        (string)$item['start']
                    ),
                    end: new DateTimeImmutable(
                        (string)$item['end']
                    ),
                    value: (float)$item['value'],
                    unit: (string)$item['unit'],
                    validationStatus:
                        (int)$item['validation_status']
                );
            } catch (\Throwable) {
                return null;
            }
        }

        return $measurements;
    }

    /**
     * @param list<Measurement> $measurements
     */
    public function put(
        string $stationId,
        array $measurements
    ): void {
        $this->ensureDirectory();

        $data = array_map(
            static fn (Measurement $measurement): array => [
                'station_id' =>
                    $measurement->stationId,

                'station_name' =>
                    $measurement->stationName,

                'pollutant_id' =>
                    $measurement->pollutantId,

                'pollutant_name' =>
                    $measurement->pollutantName,

                'start' =>
                    $measurement->start->format(DATE_ATOM),

                'end' =>
                    $measurement->end->format(DATE_ATOM),

                'value' =>
                    $measurement->value,

                'unit' =>
                    $measurement->unit,

                'validation_status' =>
                    $measurement->validationStatus,
            ],
            $measurements
        );

        $json = json_encode(
            $data,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        $filename = $this->filename($stationId);
        $temporary =
            $filename
            . '.tmp.'
            . getmypid();

        if (
            file_put_contents(
                $temporary,
                $json,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'Unable to write measurement cache.'
            );
        }

        if (!rename($temporary, $filename)) {
            @unlink($temporary);

            throw new RuntimeException(
                'Unable to finalize measurement cache.'
            );
        }
    }

    private function filename(
        string $stationId
    ): string {
        $safeStationId = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $stationId
        );

        return sprintf(
            '%s/%s.json',
            rtrim($this->directory, '/'),
            $safeStationId
        );
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (
            !mkdir(
                $this->directory,
                0775,
                true
            )
            && !is_dir($this->directory)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Unable to create cache directory: %s',
                    $this->directory
                )
            );
        }
    }
}