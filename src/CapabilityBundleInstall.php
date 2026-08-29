<?php

namespace Quain\Core;

class CapabilityBundleInstall
{
    /**
     * @param list<string> $installed
     * @param list<array{code: string, detail?: string, path?: string, expected?: string, actual?: string, reason?: array<string, mixed>}> $issues
     */
    public function __construct(
        public readonly string $identity,
        public readonly string $checksum,
        public readonly ?BundleProvenance $provenance,
        public readonly array $installed,
        public readonly array $issues = [],
    ) {}

    public function installedSuccessfully(): bool
    {
        return $this->issues === [];
    }
}
