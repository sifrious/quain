<?php

namespace Quain\Core;

class CapabilityBundleInstaller
{
    public function __construct(private readonly CapabilityBundleVerifier $verifier = new CapabilityBundleVerifier()) {}

    public function install(string $bundlePath, string $installPath): CapabilityBundleInstall
    {
        $verification = $this->verifier->verify($bundlePath);

        if (! $verification->verified()) {
            return new CapabilityBundleInstall(
                identity: $verification->identity,
                checksum: $verification->checksum,
                provenance: $verification->provenance,
                installed: [],
                issues: $verification->issues,
            );
        }

        $targetRoot = rtrim($installPath, '/').'/'.$verification->identity;

        if (! is_dir($targetRoot) && ! mkdir($targetRoot, 0755, true) && ! is_dir($targetRoot)) {
            return new CapabilityBundleInstall(
                identity: $verification->identity,
                checksum: $verification->checksum,
                provenance: $verification->provenance,
                installed: [],
                issues: [['code' => 'install-target-unwritable', 'path' => $targetRoot]],
            );
        }

        $installed = [];

        foreach ($verification->manifests as $manifest) {
            $destination = $targetRoot.'/'.$manifest->id.'.json';
            file_put_contents($destination, json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            $installed[] = $destination;
        }

        sort($installed);

        return new CapabilityBundleInstall(
            identity: $verification->identity,
            checksum: $verification->checksum,
            provenance: $verification->provenance,
            installed: $installed,
        );
    }
}
