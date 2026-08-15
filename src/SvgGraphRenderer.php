<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class SvgGraphRenderer
{
    public function __construct(
        private readonly XAxisRenderer $xAxisRenderer =
            new XAxisRenderer(),
        private readonly YAxisRenderer $yAxisRenderer =
            new YAxisRenderer(),
    ) {
    }

    public function render(
        array $points,
        GraphLayout $layout,
        string $timezone,
        int $firstTimestamp,
        int $lastTimestamp
    ): string {
        if (count($points) < 2) {
            throw new \RuntimeException(
                'Not enough points to draw graph.'
            );
        }

        $points =
            $this->normalisePoints($points);

        if (
            $layout->plotWidth() <= 0
            || $layout->plotHeight() <= 0
        ) {
            throw new \RuntimeException(
                'Invalid graph layout dimensions.'
            );
        }

        if ($lastTimestamp <= $firstTimestamp) {
            throw new \RuntimeException(
                'The graph time range must be positive.'
            );
        }

        $values =
            array_column(
                $points,
                'value'
            );

        $dataMinimum =
            (float)min($values);

        $dataMaximum =
            (float)max($values);

        /*
         * Build a rounded Y-axis scale.
         *
         * Example:
         * data from 10.2 to 27.8
         * becomes ticks at 10, 15, 20, 25 and 30.
         */
        $yAxisScale =
            NiceYAxisScale::fromRange(
                dataMinimum: $dataMinimum,
                dataMaximum: $dataMaximum,
                targetTickCount: $layout->yTickCount
            );

        /*
         * The graph itself must use the same rounded minimum and
         * maximum as the Y-axis ticks.
         *
         * The X-axis range is explicitly supplied by the caller.
         * It therefore represents the requested graph window,
         * independently of the timestamps of the first and last
         * available measurements.
         */
        $scaler =
            new GraphScaler(
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp,
                minimumValue: $yAxisScale->minimum,
                maximumValue: $yAxisScale->maximum,
                layout: $layout
            );

        $polyline =
            $this->buildPolyline(
                points: $points,
                scaler: $scaler
            );

        $timezoneObject =
            new \DateTimeZone(
                $timezone
            );

        $xTicks =
            $this->xAxisRenderer->createTicks(
                firstTimestamp: $firstTimestamp,
                lastTimestamp: $lastTimestamp,
                scaler: $scaler,
                layout: $layout,
                timezone: $timezoneObject
            );

        $xAxisSvg =
            $this->xAxisRenderer->render(
                ticks: $xTicks,
                layout: $layout
            );

        $yAxisSvg =
            $this->yAxisRenderer->render(
                scale: $yAxisScale,
                scaler: $scaler,
                layout: $layout
            );

        return $this->assembleSvg(
            layout: $layout,
            polyline: $polyline,
            xAxisSvg: $xAxisSvg,
            yAxisSvg: $yAxisSvg
        );
    }

    /**
     * @return array<int, array{timestamp: int, value: float}>
     */
    private function normalisePoints(
        array $points
    ): array {
        $normalised = [];

        foreach ($points as $index => $point) {
            if (
                !is_array($point)
                || !array_key_exists('timestamp', $point)
                || !array_key_exists('value', $point)
                || !is_numeric($point['timestamp'])
                || !is_numeric($point['value'])
            ) {
                throw new \RuntimeException(
                    "Invalid graph point at index {$index}."
                );
            }

            $timestamp =
                (int)$point['timestamp'];

            $value =
                (float)$point['value'];

            if (!is_finite($value)) {
                continue;
            }

            $normalised[] = [
                'timestamp' => $timestamp,
                'value' => $value,
            ];
        }

        usort(
            $normalised,
            static fn(array $left, array $right): int =>
                $left['timestamp']
                <=>
                $right['timestamp']
        );

        if (count($normalised) < 2) {
            throw new \RuntimeException(
                'Not enough valid points to draw graph.'
            );
        }

        return $normalised;
    }

    private function buildPolyline(
        array $points,
        GraphScaler $scaler
    ): string {
        $coordinates = [];

        foreach ($points as $point) {
            $coordinates[] =
                $this->number(
                    $scaler->x(
                        $point['timestamp']
                    )
                )
                . ','
                . $this->number(
                    $scaler->y(
                        $point['value']
                    )
                );
        }

        return implode(
            ' ',
            $coordinates
        );
    }

    private function assembleSvg(
        GraphLayout $layout,
        string $polyline,
        string $xAxisSvg,
        string $yAxisSvg
    ): string {
        $axisRight =
            $layout->axisRight();

        $axisBottom =
            $layout->axisBottom();

        return <<<SVG
<svg
xmlns="http://www.w3.org/2000/svg"
width="{$layout->width}"
height="{$layout->height}"
viewBox="0 0 {$layout->width} {$layout->height}"
role="img"
aria-label="Air quality measurements graph">

<rect
x="0"
y="0"
width="{$layout->width}"
height="{$layout->height}"
fill="white"/>

<line
x1="{$layout->marginLeft}"
y1="{$layout->marginTop}"
x2="{$layout->marginLeft}"
y2="$axisBottom"
stroke="black"
stroke-width="{$layout->axisStrokeWidth}"/>

<line
x1="{$layout->marginLeft}"
y1="$axisBottom"
x2="$axisRight"
y2="$axisBottom"
stroke="black"
stroke-width="{$layout->axisStrokeWidth}"/>

<g
font-family="Arial, Helvetica, sans-serif"
font-size="{$layout->fontSize}"
font-weight="700"
fill="black">

$yAxisSvg

$xAxisSvg

</g>

<polyline
points="$polyline"
fill="none"
stroke="black"
stroke-width="{$layout->curveStrokeWidth}"
stroke-linejoin="round"
stroke-linecap="round"/>

</svg>
SVG;
    }

    private function number(
        float $value
    ): string {
        return number_format(
            $value,
            1,
            '.',
            ''
        );
    }
}