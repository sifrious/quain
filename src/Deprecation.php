<?php

namespace Quain\Core;

class Deprecation
{
    public function __construct(
        public readonly string $replacement,
        public readonly string $firstDeprecatedIn,
        public readonly string $removalCondition,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            replacement: (string) ($data['replacement'] ?? ''),
            firstDeprecatedIn: (string) ($data['firstDeprecatedIn'] ?? ''),
            removalCondition: (string) ($data['removalCondition'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'replacement' => $this->replacement,
            'firstDeprecatedIn' => $this->firstDeprecatedIn,
            'removalCondition' => $this->removalCondition,
        ];
    }
}
