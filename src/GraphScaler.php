<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class GraphScaler
{
    public function __construct(
        private readonly int $firstTimestamp,
        private readonly int $lastTimestamp,
        private readonly float $minimumValue,
        private readonly float $maximumValue,
        private readonly GraphLayout $layout,
    ) {
        if ($lastTimestamp <= $firstTimestamp) {
            throw new \InvalidArgumentException(
                'The graph time range must be positive.'
            );
        }

        if ($maximumValue <= $minimumValue) {
            throw new \InvalidArgumentException(
                'The graph value range must be positive.'
            );
        }
    }

    public function x(int $timestamp): float
    {
        $duration =
            $this->lastTimestamp
            - $this->firstTimestamp;

        return $this->layout->marginLeft
            + (
                ($timestamp - $this->firstTimestamp)
                / $duration
            )
            * $this->layout->plotWidth();
    }

    public function y(float $value): float
    {
        $range =
            $this->maximumValue
            - $this->minimumValue;

        return $this->layout->marginTop
            + (
                1
                - (($value - $this->minimumValue) / $range)
            )
            * $this->layout->plotHeight();
    }
}