<?php

namespace Quain\Core;

class CompatibilityConstraint
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $range = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            range: isset($data['range']) ? (string) $data['range'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'range' => $this->range,
        ], fn (mixed $value) => $value !== null);
    }
}
