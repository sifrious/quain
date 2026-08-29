<?php

namespace Quain\Core;

use RuntimeException;

class UnsupportedContract extends RuntimeException
{
    public function __construct(
        public readonly string $requested,
        public readonly array $supported,
        public readonly string $change = 'breaking',
        public readonly ?string $detail = null,
    ) {
        parent::__construct($detail ?? "Unsupported contract version [{$requested}].");
    }

    public function toArray(): array
    {
        return [
            'code' => 'unsupported_contract_version',
            'requested' => $this->requested,
            'supported' => $this->supported,
            'change' => $this->change,
        ];
    }
}
