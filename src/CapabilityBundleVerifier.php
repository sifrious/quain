<?php

namespace Quain\Core;

use Throwable;

class CapabilityBundleVerifier
{
    public function verify(string $bundlePath): CapabilityBundleVerification
    {
        $descriptorPath = rtrim($bundlePath, '/').'/bundle.json';

        if (! is_file($descriptorPath)) {
            return new CapabilityBundleVerification(
                identity: '',
                checksum: '',
                provenance: null,
                manifests: [],
                issues: [['code' => 'missing-bundle-descriptor', 'path' => $descriptorPath]],
            );
        }

        $descriptor = json_decode(file_get_contents($descriptorPath), true);

        if (! is_array($descriptor)) {
            return new CapabilityBundleVerification(
                identity: '',
                checksum: '',
                provenance: null,
                manifests: [],
                issues: [['code' => 'invalid-bundle-descriptor', 'path' => $descriptorPath]],
            );
        }

        $identity = (string) ($descriptor['identity'] ?? '');
        $declaredChecksum = (string) ($descriptor['checksum'] ?? '');
        $provenance = isset($descriptor['provenance']) && is_array($descriptor['provenance'])
            ? BundleProvenance::fromArray($descriptor['provenance'])
            : null;

        $entries = is_array($descriptor['capabilities'] ?? null) ? $descriptor['capabilities'] : [];

        $issues = [];
        $manifests = [];
        $chunks = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                $issues[] = ['code' => 'invalid-capability-entry', 'path' => $descriptorPath];

                continue;
            }

            $relativePath = (string) ($entry['path'] ?? '');
            $manifestPath = rtrim($bundlePath, '/').'/'.$relativePath;

            if ($relativePath === '' || ! is_file($manifestPath)) {
                $issues[] = ['code' => 'missing-capability-manifest', 'path' => $relativePath];

                continue;
            }

            $contents = file_get_contents($manifestPath);
            $actual = hash('sha256', $contents);
            $expected = isset($entry['sha256']) ? (string) $entry['sha256'] : $actual;

            if ($expected !== $actual) {
                $issues[] = [
                    'code' => 'capability-checksum-mismatch',
                    'path' => $relativePath,
                    'expected' => $expected,
                    'actual' => $actual,
                ];

                continue;
            }

            $chunks[] = "{$relativePath}:{$actual}";

            try {
                $manifests[] = CapabilityManifest::fromJson($contents);
            } catch (UnsupportedContract $error) {
                $issues[] = [
                    'code' => 'unsupported-capability-contract',
                    'path' => $relativePath,
                    'reason' => $error->toArray(),
                ];
            } catch (Throwable $error) {
                $issues[] = [
                    'code' => 'invalid-capability-manifest',
                    'path' => $relativePath,
                    'detail' => $error->getMessage(),
                ];
            }
        }

        sort($chunks);
        $computedChecksum = hash('sha256', implode("\n", $chunks));

        if ($declaredChecksum !== '' && $declaredChecksum !== $computedChecksum) {
            $issues[] = [
                'code' => 'bundle-checksum-mismatch',
                'expected' => $declaredChecksum,
                'actual' => $computedChecksum,
            ];
        }

        usort($issues, fn (array $a, array $b) => [$a['code'], $a['path'] ?? ''] <=> [$b['code'], $b['path'] ?? '']);
        usort($manifests, fn (CapabilityManifest $a, CapabilityManifest $b) => strcmp($a->id, $b->id));

        return new CapabilityBundleVerification(
            identity: $identity,
            checksum: $computedChecksum,
            provenance: $provenance,
            manifests: $manifests,
            issues: $issues,
        );
    }
}
