<?php

namespace Quain\Core;

class CapabilityDependency
{
    public function __construct(
        public readonly string $id,
        public readonly bool $optional = false,
        public readonly ?string $version = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            optional: (bool) ($data['optional'] ?? false),
            version: isset($data['version']) ? (string) $data['version'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'optional' => $this->optional ?: null,
            'version' => $this->version,
        ], fn (mixed $value) => $value !== null);
    }
}
