<?php

namespace Quain\Core;

class CapabilityPort
{
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'string',
        public readonly bool $required = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'string'),
            required: (bool) ($data['required'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'required' => $this->required,
        ];
    }
}
