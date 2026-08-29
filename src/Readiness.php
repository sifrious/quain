<?php

namespace Quain\Core;

class Readiness
{
    public function __construct(
        public readonly string $condition,
        public readonly bool $required = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            condition: (string) ($data['condition'] ?? ''),
            required: (bool) ($data['required'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'condition' => $this->condition,
            'required' => $this->required,
        ];
    }
}
