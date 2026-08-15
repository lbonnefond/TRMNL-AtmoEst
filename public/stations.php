<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlAtmoEst\AtmoGrandEstClient;
use LBonnefond\TrmnlAtmoEst\PollutantRegistry;
use LBonnefond\TrmnlAtmoEst\StationProvider;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://trmnl.com');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pollutantKey = strtolower(
        trim((string)($_GET['pollutant'] ?? ''))
    );

    if ($pollutantKey === '') {
        throw new InvalidArgumentException(
            'Missing pollutant parameter.'
        );
    }

    $pollutants = new PollutantRegistry();

    if (!$pollutants->exists($pollutantKey)) {
        throw new InvalidArgumentException(
            sprintf(
                'Unknown pollutant: %s',
                $pollutantKey
            )
        );
    }

    $pollutant = $pollutants->get(
        $pollutantKey
    );

    $provider = new StationProvider(
        client: new AtmoGrandEstClient(),
        pollutants: $pollutants
    );

    $options = [];

    foreach ($provider->all() as $station) {
        if (
            !$station->supportsPollutant(
                $pollutant->id
            )
        ) {
            continue;
        }

        $options[] = [
            $station->label() => $station->id,
        ];
    }

    echo json_encode(
        $options,
        JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );
} catch (InvalidArgumentException $exception) {
    http_response_code(400);

    echo json_encode(
        [
            'message' => $exception->getMessage(),
        ],
        JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode(
        [
            'message' => 'Unable to retrieve stations.',
        ],
        JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );
}