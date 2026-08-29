<?php

namespace Quain\Core;

class VocabularyReference
{
    public function __construct(
        public readonly string $kind,
        public readonly string $id,
        public readonly ?string $version = null,
    ) {
        VocabularyKind::assert($kind);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            kind: (string) ($data['kind'] ?? ''),
            id: (string) ($data['id'] ?? ''),
            version: isset($data['version']) ? (string) $data['version'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'kind' => $this->kind,
            'id' => $this->id,
            'version' => $this->version,
        ], fn (mixed $value) => $value !== null);
    }
}
