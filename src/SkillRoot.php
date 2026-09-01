<?php

namespace Quain\Core;

/**
 * One place skills are read from.
 *
 * `kind` is provenance a reader needs in order to judge a copy, not a
 * permission: exactly one root is ever written to, and `rank()` is the order
 * that decides which copy of an identical skill is quoted as the primary one.
 */
class SkillRoot
{
    public const LIBRARY = 'library';

    public const PLUGIN = 'plugin';

    public const PROJECT = 'project';

    public function __construct(
        public readonly string $path,
        public readonly string $kind = self::LIBRARY,
        public readonly ?string $label = null,
        public readonly int $depth = 1,
    ) {}

    public static function fromArray(array $root): self
    {
        return new self(
            path: (string) $root['path'],
            kind: $root['kind'] ?? self::LIBRARY,
            label: $root['label'] ?? null,
            depth: (int) ($root['depth'] ?? 1),
        );
    }

    public function writable(): bool
    {
        return $this->kind === self::LIBRARY;
    }

    public function rank(): int
    {
        return match ($this->kind) {
            self::LIBRARY => 0,
            self::PROJECT => 1,
            default => 2,
        };
    }
}
