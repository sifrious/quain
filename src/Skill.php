<?php

namespace Quain\Core;

class Skill
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $path,
        public readonly bool $hasScripts,
    ) {}

    public function summary(int $words = 18): string
    {
        $parts = preg_split('/\s+/', $this->description);

        return count($parts) <= $words
            ? $this->description
            : implode(' ', array_slice($parts, 0, $words)).'…';
    }
}
