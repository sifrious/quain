<?php

use Quain\Core\CapabilityManifest;
use Quain\Core\VocabularyKind;
use Quain\Core\VocabularyReference;

it('maps each Landing catalogue record to an explicit vocabulary-reference kind', function () {
    expect(VocabularyKind::all())->toBe([
        VocabularyKind::ALGORITHM,
        VocabularyKind::ARCHITECTURE,
        VocabularyKind::DATA_STRUCTURE,
        VocabularyKind::DESIGN_PATTERN,
        VocabularyKind::DISCIPLINE,
        VocabularyKind::VALUE,
        VocabularyKind::IDEAL,
    ]);
});

it('rejects unknown vocabulary kinds instead of inventing a second catalogue', function () {
    expect(fn () => new VocabularyReference('controller', 'landing:values/index'))
        ->toThrow(InvalidArgumentException::class, 'Unknown vocabulary kind');
});

it('preserves Landing identities as references on a capability manifest', function () {
    $manifest = CapabilityManifest::fromArray(repositoryScanPayload());
    $ids = array_map(fn (VocabularyReference $reference) => $reference->id, $manifest->vocabulary);

    expect($ids)->toContain('landing:values/reproducible-composition')
        ->and($ids)->toContain('landing:algorithms/directory-walk')
        ->and($manifest->toArray())->not->toHaveKey('controller')
        ->and($manifest->toArray())->not->toHaveKey('eloquent');
});
