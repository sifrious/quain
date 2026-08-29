<?php

namespace Quain\Core;

class Parallelism
{
    public function __construct(
        public readonly bool $allowed = false,
        public readonly ?int $max = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if ($data === null) {
            return new self;
        }

        return new self(
            allowed: (bool) ($data['allowed'] ?? false),
            max: isset($data['max']) ? (int) $data['max'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'allowed' => $this->allowed,
            'max' => $this->max,
        ], fn (mixed $value) => $value !== null);
    }
}
