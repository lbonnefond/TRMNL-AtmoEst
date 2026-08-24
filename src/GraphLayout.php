<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class GraphLayout
{
    public function __construct(
        public readonly string $name,
        public readonly int $width,
        public readonly int $height,
        public readonly int $marginLeft,
        public readonly int $marginRight,
        public readonly int $marginTop,
        public readonly int $marginBottom,
        public readonly int $fontSize,
        public readonly float $curveStrokeWidth,
        public readonly float $axisStrokeWidth,
        public readonly float $gridStrokeWidth,
        public readonly int $yTickCount,
        public readonly int $xTickLength,
        public readonly int $yTickLength,
        public readonly int $xLabelOffset,
        public readonly int $yLabelGap,
        public readonly float $xLabelGap,
        public readonly float $labelWidthFactor,
        public readonly bool $showHorizontalGrid = true,
    ) {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException(
                'Graph dimensions must be positive.'
            );
        }

        if ($yTickCount < 2) {
            throw new \InvalidArgumentException(
                'At least two Y-axis ticks are required.'
            );
        }
    }

    public static function full(): self
    {
        return new self(
            name: 'full',
            width: 800,
            height: 430,
            marginLeft: 50,
            marginRight: 20,
            marginTop: 20,
            marginBottom: 50,
            fontSize: 15,
            curveStrokeWidth: 3.0,
            axisStrokeWidth: 1.5,
            gridStrokeWidth: 1.3,
            yTickCount: 5,
            xTickLength: 6,
            yTickLength: 5,
            xLabelOffset: 18,
            yLabelGap: 5,
            xLabelGap: 8.0,
            labelWidthFactor: 0.62,
        );
    }

    /**
     * Wide and short Mashup region.
     */
    public static function halfHorizontal(): self
    {
        return new self(
            name: 'half-horizontal',
            width: 800,
            height: 205,
            marginLeft: 46,
            marginRight: 16,
            marginTop: 12,
            marginBottom: 38,
            fontSize: 13,
            curveStrokeWidth: 2.5,
            axisStrokeWidth: 1.4,
            gridStrokeWidth: 1.1,
            yTickCount: 3,
            xTickLength: 5,
            yTickLength: 4,
            xLabelOffset: 16,
            yLabelGap: 4,
            xLabelGap: 7.0,
            labelWidthFactor: 0.62,
        );
    }

    /**
     * Narrow and tall Mashup region.
     */
    public static function halfVertical(): self
    {
        return new self(
            name: 'half-vertical',
            width: 390,
            height: 430,
            marginLeft: 44,
            marginRight: 14,
            marginTop: 18,
            marginBottom: 44,
            fontSize: 13,
            curveStrokeWidth: 2.5,
            axisStrokeWidth: 1.4,
            gridStrokeWidth: 1.1,
            yTickCount: 5,
            xTickLength: 5,
            yTickLength: 4,
            xLabelOffset: 16,
            yLabelGap: 4,
            xLabelGap: 7.0,
            labelWidthFactor: 0.62,
        );
    }

    /**
     * One quadrant of a 2 × 2 Mashup.
     */
    public static function quadrant(): self
    {
        return new self(
            name: 'quadrant',
            width: 390,
            height: 205,
            marginLeft: 42,
            marginRight: 12,
            marginTop: 12,
            marginBottom: 36,
            fontSize: 13,
            curveStrokeWidth: 2.2,
            axisStrokeWidth: 1.3,
            gridStrokeWidth: 1.0,
            yTickCount: 3,
            xTickLength: 4,
            yTickLength: 4,
            xLabelOffset: 15,
            yLabelGap: 3,
            xLabelGap: 6.0,
            labelWidthFactor: 0.62,
        );
    }

    public static function xLandscapePanel(): self
    {
        return new self(
            name: 'x-landscape-panel',
            width: 500,
            height: 300,
            marginLeft: 52,
            marginRight: 18,
            marginTop: 16,
            marginBottom: 42,
            fontSize: 15,
            curveStrokeWidth: 2.8,
            axisStrokeWidth: 1.5,
            gridStrokeWidth: 1.2,
            yTickCount: 4,
            xTickLength: 5,
            yTickLength: 5,
            xLabelOffset: 17,
            yLabelGap: 5,
            xLabelGap: 8.0,
            labelWidthFactor: 0.62,
        );
    }

    public static function xPortraitPanel(): self
    {
        return new self(
            name: 'x-portrait-panel',
            width: 740,
            height: 400,
            marginLeft: 52,
            marginRight: 18,
            marginTop: 18,
            marginBottom: 46,
            fontSize: 15,
            curveStrokeWidth: 3.0,
            axisStrokeWidth: 1.5,
            gridStrokeWidth: 1.2,
            yTickCount: 5,
            xTickLength: 6,
            yTickLength: 5,
            xLabelOffset: 18,
            yLabelGap: 5,
            xLabelGap: 8.0,
            labelWidthFactor: 0.62,
        );
    }

    public static function twoByTwo(): self
    {
        return self::quadrant();
    }

    public function plotWidth(): int
    {
        return $this->width
            - $this->marginLeft
            - $this->marginRight;
    }

    public function plotHeight(): int
    {
        return $this->height
            - $this->marginTop
            - $this->marginBottom;
    }

    public function axisRight(): int
    {
        return $this->width - $this->marginRight;
    }

    public function axisBottom(): int
    {
        return $this->height - $this->marginBottom;
    }
}
