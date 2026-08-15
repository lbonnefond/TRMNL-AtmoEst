<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final class XAxisTick
{
    public function __construct(
        public readonly int $timestamp,
        public readonly float $x,
        public readonly string $label,
        public readonly bool $isFirst,
        public readonly bool $isLast,
        public string $anchor = 'middle',
        public float $labelLeft = 0.0,
        public float $labelRight = 0.0,
        public bool $showLabel = true,
    ) {
    }
}