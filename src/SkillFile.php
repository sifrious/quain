<?php

namespace Quain\Core;

class SkillFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $absolutePath,
        public readonly int $bytes,
        public readonly string $kind,
    ) {}

    public function extension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function humanSize(): string
    {
        if ($this->bytes < 1024) {
            return $this->bytes.' B';
        }

        return round($this->bytes / 1024, 1).' KB';
    }
}
