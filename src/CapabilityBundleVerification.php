<?php

namespace Quain\Core;

class CapabilityBundleVerification
{
    /**
     * @param list<CapabilityManifest> $manifests
     * @param list<array{code: string, detail?: string, path?: string, expected?: string, actual?: string, reason?: array<string, mixed>}> $issues
     */
    public function __construct(
        public readonly string $identity,
        public readonly string $checksum,
        public readonly ?BundleProvenance $provenance,
        public readonly array $manifests,
        public readonly array $issues = [],
    ) {}

    public function verified(): bool
    {
        return $this->issues === [];
    }
}
