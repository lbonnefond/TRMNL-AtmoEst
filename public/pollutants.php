<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://trmnl.com');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

echo json_encode(
    [
        ['PM10' => 'pm10'],
        ['PM2.5' => 'pm25'],
        ['O₃' => 'o3'],
        ['NO₂' => 'no2'],
    ],
    JSON_THROW_ON_ERROR
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);