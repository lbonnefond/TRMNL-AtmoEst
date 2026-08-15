<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class YAxisRenderer
{
    public function render(
        NiceYAxisScale $scale,
        GraphScaler $scaler,
        GraphLayout $layout
    ): string {
        $elements = [];

        $tickStartX =
            $layout->marginLeft;

        $tickEndX =
            $layout->marginLeft
            - $layout->yTickLength;

        $labelX =
            $tickEndX
            - $layout->yLabelGap;

        foreach (
            $scale->ticks as $index => $value
        ) {
            $formattedY =
                $this->number(
                    $scaler->y($value)
                );

            $label =
                $this->formatLabel(
                    value: $value,
                    decimals: $scale->decimals
                );

            /*
             * The first tick is the lower tick and coincides with
             * the solid X-axis.
             */
            if (
                $layout->showHorizontalGrid
                && $index > 0
            ) {
                $axisRight =
                    $layout->axisRight();

                $elements[] = <<<SVG
<line
x1="{$layout->marginLeft}"
y1="$formattedY"
x2="$axisRight"
y2="$formattedY"
stroke="#888888"
stroke-width="{$layout->gridStrokeWidth}"
stroke-dasharray="4 4"/>
SVG;
            }

            $elements[] = <<<SVG
<line
x1="$tickStartX"
y1="$formattedY"
x2="$tickEndX"
y2="$formattedY"
stroke="black"
stroke-width="{$layout->axisStrokeWidth}"/>

<text
x="$labelX"
y="$formattedY"
text-anchor="end"
dominant-baseline="middle">
$label
</text>
SVG;
        }

        return implode(
            "\n",
            $elements
        );
    }

    private function formatLabel(
        float $value,
        int $decimals
    ): string {
        $threshold =
            0.5
            * (10 ** (-$decimals));

        if (abs($value) < $threshold) {
            $value = 0.0;
        }

        return number_format(
            $value,
            $decimals,
            '.',
            ''
        );
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