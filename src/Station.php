<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlAtmoEst;

final readonly class Station
{
    /**
     * @param list<string> $pollutantIds
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $city,
        public string $department,
        public string $typology,
        public string $influence,
        public array $pollutantIds
    ) {
    }

    public function supportsPollutant(string $pollutantId): bool
    {
        return in_array(
            $pollutantId,
            $this->pollutantIds,
            true
        );
    }

    public function label(): string
    {
        if ($this->city === $this->name) {
            return sprintf(
                '%s (%s)',
                $this->name,
                mb_strtolower($this->influence)
            );
        }

        return sprintf(
            '%s — %s (%s)',
            $this->city,
            $this->name,
            mb_strtolower($this->influence)
        );
    }
}