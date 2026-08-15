<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class XAxisRenderer
{
    /**
     * @return XAxisTick[]
     */
    public function createTicks(
        int $firstTimestamp,
        int $lastTimestamp,
        GraphScaler $scaler,
        GraphLayout $layout,
        \DateTimeZone $timezone
    ): array {
        $timestamps = $this->createTickTimestamps(
            firstTimestamp: $firstTimestamp,
            lastTimestamp: $lastTimestamp,
            timezone: $timezone
        );

        $ticks = [];
        $lastIndex = count($timestamps) - 1;

        foreach ($timestamps as $index => $timestamp) {
            $date = (
                new \DateTimeImmutable('@' . $timestamp)
            )->setTimezone($timezone);

            $isFirst = $index === 0;
            $isLast = $index === $lastIndex;

            /*
             * Required alignment:
             *
             * First label: centred on its tick.
             * Last label: ends at its tick and extends to the left.
             */
            $anchor = $isLast
                ? 'end'
                : 'middle';

            $tick = new XAxisTick(
                timestamp: $timestamp,
                x: $scaler->x($timestamp),
                label: $date->format('H:i'),
                isFirst: $isFirst,
                isLast: $isLast,
                anchor: $anchor
            );

            $this->calculateLabelBounds(
                tick: $tick,
                layout: $layout
            );

            $ticks[] = $tick;
        }

        $this->hideOverlappingLabels(
            ticks: $ticks,
            minimumGap: $layout->xLabelGap
        );

        return $ticks;
    }

    /**
     * @param XAxisTick[] $ticks
     */
    public function render(
        array $ticks,
        GraphLayout $layout
    ): string {
        $elements = [];

        $axisBottom = $layout->axisBottom();
        $tickEndY =
            $axisBottom
            + $layout->xTickLength;

        $labelY =
            $axisBottom
            + $layout->xLabelOffset;

        foreach ($ticks as $tick) {
            $x = $this->number($tick->x);

            $elements[] = <<<SVG
<line
x1="$x"
y1="$axisBottom"
x2="$x"
y2="$tickEndY"
stroke="black"
stroke-width="{$layout->axisStrokeWidth}"/>
SVG;

            if (!$tick->showLabel) {
                continue;
            }

            $label = $this->escape($tick->label);

            $elements[] = <<<SVG
<text
x="$x"
y="$labelY"
text-anchor="{$tick->anchor}"
dominant-baseline="middle">
$label
</text>
SVG;
        }

        return implode("\n", $elements);
    }

    /**
     * @return int[]
     */
    private function createTickTimestamps(
        int $firstTimestamp,
        int $lastTimestamp,
        \DateTimeZone $timezone
    ): array {
        $timestamps = [
            $firstTimestamp
        ];

        $firstLocal = (
            new \DateTimeImmutable('@' . $firstTimestamp)
        )->setTimezone($timezone);

        $currentHour =
            (int)$firstLocal->format('H');

        $previousBoundaryHour =
            intdiv($currentHour, 6) * 6;

        $boundary =
            $firstLocal->setTime(
                $previousBoundaryHour,
                0,
                0
            );

        if ($boundary->getTimestamp() <= $firstTimestamp) {
            $boundary =
                $boundary->modify('+6 hours');
        }

        while ($boundary->getTimestamp() < $lastTimestamp) {
            $timestamps[] =
                $boundary->getTimestamp();

            $boundary =
                $boundary->modify('+6 hours');
        }

        if (
            !in_array(
                $lastTimestamp,
                $timestamps,
                true
            )
        ) {
            $timestamps[] =
                $lastTimestamp;
        }

        $timestamps =
            array_values(
                array_unique($timestamps)
            );

        sort($timestamps, SORT_NUMERIC);

        return $timestamps;
    }

    private function calculateLabelBounds(
        XAxisTick $tick,
        GraphLayout $layout
    ): void {
        $estimatedWidth =
            mb_strlen($tick->label)
            * $layout->fontSize
            * $layout->labelWidthFactor;

        if ($tick->anchor === 'end') {
            $tick->labelLeft =
                $tick->x - $estimatedWidth;

            $tick->labelRight =
                $tick->x;

            return;
        }

        if ($tick->anchor === 'start') {
            $tick->labelLeft =
                $tick->x;

            $tick->labelRight =
                $tick->x + $estimatedWidth;

            return;
        }

        $halfWidth =
            $estimatedWidth / 2;

        $tick->labelLeft =
            $tick->x - $halfWidth;

        $tick->labelRight =
            $tick->x + $halfWidth;
    }

    /**
     * Preserve the actual first and last observation labels.
     *
     * Intermediate labels are removed only when their calculated text
     * bounds would overlap another retained label.
     *
     * @param XAxisTick[] $ticks
     */
    private function hideOverlappingLabels(
        array $ticks,
        float $minimumGap
    ): void {
        $tickCount = count($ticks);

        if ($tickCount <= 2) {
            return;
        }

        $lastIndex = $tickCount - 1;
        $lastTick = $ticks[$lastIndex];

        /*
         * The first and last labels always have priority.
         */
        $ticks[0]->showLabel = true;
        $lastTick->showLabel = true;

        $previousVisibleTick = $ticks[0];

        for (
            $index = 1;
            $index < $lastIndex;
            $index++
        ) {
            $tick = $ticks[$index];

            $overlapsPrevious =
                $tick->labelLeft
                <
                ($previousVisibleTick->labelRight + $minimumGap);

            $overlapsLast =
                $tick->labelRight
                >
                ($lastTick->labelLeft - $minimumGap);

            if ($overlapsPrevious || $overlapsLast) {
                $tick->showLabel = false;
                continue;
            }

            $tick->showLabel = true;
            $previousVisibleTick = $tick;
        }
    }

    private function number(float $value): string
    {
        return number_format(
            $value,
            1,
            '.',
            ''
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );
    }
}