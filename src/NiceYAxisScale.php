<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class NiceYAxisScale
{
    /**
     * @param list<float> $ticks
     */
    public function __construct(
        public readonly float $minimum,
        public readonly float $maximum,
        public readonly float $step,
        public readonly int $decimals,
        public readonly array $ticks
    ) {
    }

    public static function fromRange(
        float $dataMinimum,
        float $dataMaximum,
        int $targetTickCount = 5
    ): self {
        if ($targetTickCount < 2) {
            $targetTickCount = 2;
        }

        /*
         * Ensure that minimum and maximum are in the correct order.
         */
        if ($dataMinimum > $dataMaximum) {
            [
                $dataMinimum,
                $dataMaximum,
            ] = [
                $dataMaximum,
                $dataMinimum,
            ];
        }

        /*
         * Avoid a zero-sized range when every observation has
         * exactly the same value.
         */
        if ($dataMinimum === $dataMaximum) {
            $padding =
                abs($dataMinimum) > 0.0
                    ? abs($dataMinimum) * 0.05
                    : 1.0;

            $dataMinimum -= $padding;
            $dataMaximum += $padding;
        }

        $range =
            $dataMaximum - $dataMinimum;

        $rawStep =
            $range / ($targetTickCount - 1);

        $step =
            self::niceStep(
                $rawStep
            );

        /*
         * Round the displayed limits to exact multiples of the
         * selected step.
         */
        $minimum =
            floor(
                $dataMinimum / $step
            )
            * $step;

        $maximum =
            ceil(
                $dataMaximum / $step
            )
            * $step;

        $decimals =
            self::decimalsForStep(
                $step
            );

        /*
         * Calculate an integer number of intervals.
         *
         * Integer indexing prevents cumulative floating-point
         * errors and avoids generating an additional tick beyond
         * the maximum value.
         */
        $intervalCount =
            (int)round(
                ($maximum - $minimum)
                / $step
            );

        /*
         * Recalculate the maximum from the interval count so that
         * it corresponds exactly to the last generated tick.
         */
        $maximum =
            $minimum
            + ($intervalCount * $step);

        $ticks = [];

        for (
            $index = 0;
            $index <= $intervalCount;
            $index++
        ) {
            $value =
                $minimum
                + ($index * $step);

            $ticks[] =
                round(
                    $value,
                    $decimals
                );
        }

        /*
         * Use the generated ticks as the authoritative limits.
         */
        $minimum =
            $ticks[0];

        $maximum =
            $ticks[
                array_key_last(
                    $ticks
                )
            ];

        return new self(
            minimum: $minimum,
            maximum: $maximum,
            step: $step,
            decimals: $decimals,
            ticks: $ticks
        );
    }

    private static function niceStep(
        float $rawStep
    ): float {
        if ($rawStep <= 0.0) {
            return 1.0;
        }

        $exponent =
            floor(
                log10(
                    $rawStep
                )
            );

        $magnitude =
            10 ** $exponent;

        $fraction =
            $rawStep / $magnitude;

        /*
         * Use a conventional 1-2-5-10 progression.
         *
         * The previous 2.5 step could produce values such as:
         *
         * 17.5, 22.5, 27.5
         *
         * which were then displayed as:
         *
         * 18, 23, 28
         *
         * because labels greater than or equal to 1 were formatted
         * without decimals.
         *
         * Using 1-2-5-10 provides simpler and more regular labels,
         * particularly for temperature and atmospheric pressure.
         */
        $niceFraction =
            match (true) {
                $fraction <= 1.5 =>
                    1.0,

                $fraction <= 3.0 =>
                    2.0,

                $fraction <= 7.5 =>
                    5.0,

                default =>
                    10.0,
            };

        return
            $niceFraction
            * $magnitude;
    }

    private static function decimalsForStep(
        float $step
    ): int {
        /*
         * Steps greater than or equal to 1 use integer labels.
         *
         * Examples:
         *
         * 18, 20, 22
         * 1004, 1006, 1008
         */
        if ($step >= 1.0) {
            return 0;
        }

        /*
         * Determine the number of decimals required to represent
         * small steps such as 0.5, 0.2 or 0.05.
         */
        $decimals = 0;
        $scaledStep = $step;

        while (
            $decimals < 3
            && abs(
                $scaledStep
                - round($scaledStep)
            ) > 1e-9
        ) {
            $scaledStep *= 10.0;
            $decimals++;
        }

        return $decimals;
    }
}