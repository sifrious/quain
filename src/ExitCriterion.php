<?php

namespace Quain\Core;

class ExitCriterion
{
    public function __construct(
        public readonly string $statement,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            statement: (string) ($data['statement'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return ['statement' => $this->statement];
    }
}
