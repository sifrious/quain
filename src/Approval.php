<?php

namespace Quain\Core;

class Approval
{
    public function __construct(
        public readonly string $role,
        public readonly string $when = 'before-run',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            role: (string) ($data['role'] ?? ''),
            when: (string) ($data['when'] ?? 'before-run'),
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'when' => $this->when,
        ];
    }
}
