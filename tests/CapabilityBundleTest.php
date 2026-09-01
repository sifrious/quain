<?php

use Quain\Core\CapabilityBundleInstaller;
use Quain\Core\CapabilityBundleVerifier;
use Quain\Core\PublishedContracts;

function makeBundleFixture(array $manifestOverrides = [], ?string $manifestChecksum = null, ?string $bundleChecksum = null): string
{
    $root = sys_get_temp_dir().'/quain-bundle-'.uniqid();
    mkdir($root.'/manifests', 0755, true);

    $payload = repositoryScanPayload();

    foreach ($manifestOverrides as $key => $value) {
        $payload[$key] = $value;
    }

    $manifestPath = $root.'/manifests/repository-scan.json';
    $manifestContents = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    file_put_contents($manifestPath, $manifestContents);

    $actualManifestChecksum = hash('sha256', $manifestContents);
    $declaredManifestChecksum = $manifestChecksum ?? $actualManifestChecksum;
    $actualBundleChecksum = hash('sha256', "manifests/repository-scan.json:{$actualManifestChecksum}");
    $declaredBundleChecksum = $bundleChecksum ?? $actualBundleChecksum;

    file_put_contents($root.'/bundle.json', json_encode([
        'identity' => 'bundle.catalogue.core',
        'checksum' => $declaredBundleChecksum,
        'provenance' => [
            'source' => 'github.com/sifrious/quain',
            'reference' => 'refs/tags/v0.1.0',
            'capturedAt' => '2026-08-29T00:00:00Z',
        ],
        'capabilities' => [[
            'path' => 'manifests/repository-scan.json',
            'sha256' => $declaredManifestChecksum,
        ]],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    return $root;
}

it('verifies identity checksum provenance and manifest contract without executing bundle contents', function () {
    $bundle = makeBundleFixture();
    $verification = (new CapabilityBundleVerifier())->verify($bundle);
    removeTree($bundle);

    expect($verification->verified())->toBeTrue()
        ->and($verification->identity)->toBe('bundle.catalogue.core')
        ->and($verification->checksum)->not->toBe('')
        ->and($verification->provenance?->source)->toBe('github.com/sifrious/quain')
        ->and($verification->provenance?->reference)->toBe('refs/tags/v0.1.0')
        ->and($verification->manifests)->toHaveCount(1)
        ->and($verification->manifests[0]->contractVersion)->toBe(PublishedContracts::MANIFEST_V1);
});

it('returns checksum verification failures as data', function () {
    $bundle = makeBundleFixture(manifestChecksum: str_repeat('a', 64));
    $verification = (new CapabilityBundleVerifier())->verify($bundle);
    removeTree($bundle);

    $codes = array_map(fn (array $issue) => $issue['code'], $verification->issues);
    $manifestIssue = array_values(array_filter(
        $verification->issues,
        fn (array $issue) => $issue['code'] === 'capability-checksum-mismatch',
    ));

    expect($verification->verified())->toBeFalse()
        ->and($codes)->toContain('capability-checksum-mismatch')
        ->and($codes)->toContain('bundle-checksum-mismatch')
        ->and($manifestIssue[0]['path'])->toBe('manifests/repository-scan.json');
});

it('returns unsupported breaking contract versions as data', function () {
    $bundle = makeBundleFixture(['contractVersion' => 'quain.capability-manifest.2']);
    $verification = (new CapabilityBundleVerifier())->verify($bundle);
    removeTree($bundle);

    expect($verification->verified())->toBeFalse()
        ->and($verification->issues[0]['code'])->toBe('unsupported-capability-contract')
        ->and($verification->issues[0]['reason']['code'])->toBe('unsupported_contract_version');
});

it('installs verified bundles without executing their contents', function () {
    $bundle = makeBundleFixture();
    $installRoot = sys_get_temp_dir().'/quain-install-'.uniqid();

    $result = (new CapabilityBundleInstaller())->install($bundle, $installRoot);

    expect($result->installedSuccessfully())->toBeTrue()
        ->and($result->identity)->toBe('bundle.catalogue.core')
        ->and($result->installed)->toHaveCount(1)
        ->and(is_file($result->installed[0]))->toBeTrue();

    $installed = json_decode(file_get_contents($result->installed[0]), true);

    expect($installed['id'])->toBe('repository-scan');

    removeTree($bundle);
    removeTree($installRoot);
});
