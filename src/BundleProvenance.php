<?php

namespace Quain\Core;

class BundleProvenance
{
    public function __construct(
        public readonly string $source,
        public readonly ?string $reference = null,
        public readonly ?string $capturedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            source: (string) ($data['source'] ?? ''),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            capturedAt: isset($data['capturedAt']) ? (string) $data['capturedAt'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'source' => $this->source,
            'reference' => $this->reference,
            'capturedAt' => $this->capturedAt,
        ], fn (mixed $value) => $value !== null);
    }
}
