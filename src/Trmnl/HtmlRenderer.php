<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst\Trmnl;

use DateTimeImmutable;
use DateTimeZone;
use LBonnefond\TrmnlAtmoEst\GraphLayout;
use LBonnefond\TrmnlAtmoEst\Pollutant;

final class HtmlRenderer
{
    public function render(
        string $svg,
        GraphLayout $layout,
        string $stationName,
        Pollutant $pollutant,
        ?DateTimeImmutable $lastMeasurement = null,
        string $timezone = 'Europe/Paris'
    ): string {
        $stationName = $this->escape($stationName);
        $pollutantLabel = $this->escape($pollutant->label);
        $unit = $this->escape($pollutant->unit);

        $lastMeasurementHtml = '';

        if ($lastMeasurement !== null) {
            $localDate = $lastMeasurement->setTimezone(
                new DateTimeZone($timezone)
            );

            $lastMeasurementHtml = sprintf(
                '<span class="atmo-meta">Dernière mesure : %s</span>',
                $this->escape($localDate->format('H:i'))
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>
<title>{$pollutantLabel} — {$stationName}</title>

<style>
html,
body {
    margin: 0;
    padding: 0;
    background: white;
    color: black;
    font-family: Arial, Helvetica, sans-serif;
}

.atmo-plugin {
    box-sizing: border-box;
    width: {$layout->width}px;
    height: {$layout->height}px;
    overflow: hidden;
    background: white;
}

.atmo-header {
    box-sizing: border-box;
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
    padding: 3px 8px 0 8px;
    white-space: nowrap;
}

.atmo-title {
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 16px;
    font-weight: 700;
}

.atmo-pollutant {
    flex: 0 0 auto;
    font-size: 16px;
    font-weight: 700;
}

.atmo-unit {
    font-size: 11px;
    font-weight: 400;
}

.atmo-graph {
    line-height: 0;
}

.atmo-graph svg {
    display: block;
}

.atmo-footer {
    box-sizing: border-box;
    display: flex;
    justify-content: flex-end;
    padding: 0 8px;
    font-size: 9px;
    line-height: 11px;
    white-space: nowrap;
}

.atmo-meta {
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
</head>

<body>
<div class="atmo-plugin">

    <div class="atmo-header">
        <div class="atmo-title">{$stationName}</div>

        <div class="atmo-pollutant">
            {$pollutantLabel}
            <span class="atmo-unit">{$unit}</span>
        </div>
    </div>

    <div class="atmo-graph">
        {$svg}
    </div>

    <div class="atmo-footer">
        {$lastMeasurementHtml}
    </div>

</div>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
