<?php

namespace Quain\Core;

/**
 * A skill, identified by its content rather than by its folder name.
 *
 * `name` is a label. Two folders called the same thing are the same skill only
 * when their bytes agree; when they disagree they are two entries that happen
 * to share a name, and saying so is the point.
 */
class Skill
{
    /** @param  list<SkillOccurrence>  $occurrences */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $path,
        public readonly bool $hasScripts,
        public readonly string $instructionsHash = '',
        public readonly string $treeHash = '',
        public readonly array $occurrences = [],
    ) {}

    /** @param  non-empty-list<SkillOccurrence>  $occurrences  Most authoritative copy first. */
    public static function of(array $occurrences): self
    {
        $primary = $occurrences[0];

        return new self(
            name: $primary->name,
            description: $primary->description,
            path: $primary->path,
            hasScripts: $primary->hasScripts,
            instructionsHash: $primary->instructionsHash,
            treeHash: $primary->treeHash,
            occurrences: $occurrences,
        );
    }

    /** Short content address. Stable across copies, distinct across forks. */
    public function id(): string
    {
        return substr($this->treeHash, 0, 12);
    }

    public function locations(): int
    {
        return max(1, count($this->occurrences));
    }

    public function summary(int $words = 18): string
    {
        $parts = preg_split('/\s+/', $this->description);

        return count($parts) <= $words
            ? $this->description
            : implode(' ', array_slice($parts, 0, $words)).'…';
    }
}
