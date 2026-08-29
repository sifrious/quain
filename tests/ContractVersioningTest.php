<?php

use Quain\Core\CapabilityManifest;
use Quain\Core\PublishedContracts;
use Quain\Core\UnsupportedContract;

it('publishes supported contract versions for consumers', function () {
    $ids = array_column(PublishedContracts::describe(), 'id');

    expect($ids)->toContain(PublishedContracts::MANIFEST_V1)
        ->and($ids)->toContain(PublishedContracts::MANIFEST_V1_NOTES)
        ->and($ids)->toContain(PublishedContracts::MANIFEST_V0)
        ->and(PublishedContracts::classify(PublishedContracts::MANIFEST_V1))->toBe(PublishedContracts::CHANGE_COMPATIBLE)
        ->and(PublishedContracts::classify(PublishedContracts::MANIFEST_V1_NOTES))->toBe(PublishedContracts::CHANGE_ADDITIVE)
        ->and(PublishedContracts::classify(PublishedContracts::MANIFEST_V0))->toBe(PublishedContracts::CHANGE_DEPRECATED)
        ->and(PublishedContracts::classify('quain.capability-manifest.2'))->toBe(PublishedContracts::CHANGE_BREAKING);
});

it('consumes an additive contract version and ignores documented extra fields', function () {
    $payload = repositoryScanPayload();
    $payload['contractVersion'] = PublishedContracts::MANIFEST_V1_NOTES;
    $payload['notes'] = 'Operator hint added in the additive revision.';

    $manifest = CapabilityManifest::fromArray($payload);

    expect($manifest->id)->toBe('repository-scan')
        ->and($manifest->contractVersion)->toBe(PublishedContracts::MANIFEST_V1_NOTES)
        ->and($manifest->toArray())->not->toHaveKey('notes');
});

it('still consumes a deprecated contract version and names its replacement', function () {
    $payload = repositoryScanPayload();
    $payload['contractVersion'] = PublishedContracts::MANIFEST_V0;

    $manifest = CapabilityManifest::fromArray($payload);
    $deprecation = PublishedContracts::deprecation($manifest->contractVersion);

    expect($manifest->id)->toBe('repository-scan')
        ->and($deprecation)->not->toBeNull()
        ->and($deprecation->replacement)->toBe(PublishedContracts::MANIFEST_V1)
        ->and($deprecation->firstDeprecatedIn)->toBe(PublishedContracts::MANIFEST_V1)
        ->and($deprecation->removalCondition)->toContain('no published consumer');
});

it('rejects an unsupported breaking contract version with a machine-readable reason', function () {
    $payload = repositoryScanPayload();
    $payload['contractVersion'] = 'quain.capability-manifest.2';

    $caught = null;

    try {
        CapabilityManifest::fromArray($payload);
    } catch (UnsupportedContract $error) {
        $caught = $error;
    }

    expect($caught)->not->toBeNull()
        ->and($caught->toArray())->toBe([
            'code' => 'unsupported_contract_version',
            'requested' => 'quain.capability-manifest.2',
            'supported' => PublishedContracts::supported(),
            'change' => PublishedContracts::CHANGE_BREAKING,
        ]);
});

it('does not require other packages to upgrade when this catalog changes', function () {
    $payload = repositoryScanPayload();

    expect(CapabilityManifest::fromArray($payload)->contractVersion)
        ->toBe(PublishedContracts::MANIFEST_V1)
        ->and(PublishedContracts::describe())->not->toContain('funes')
        ->and(PublishedContracts::describe())->not->toContain('orbis');
});
