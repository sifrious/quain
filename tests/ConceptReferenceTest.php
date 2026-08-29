<?php

use Quain\Core\Concept\ConceptReference;
use Quain\Core\Concept\ConceptResolution;
use Quain\Core\Concept\ConceptResolutionStatus;
use Quain\Core\Concept\ConceptSnapshot;
use Quain\Core\Concept\VocabularySchemeReference;

function concept(string $scheme, string $identifier, string $version = '1'): ConceptReference
{
    return new ConceptReference(
        new VocabularySchemeReference('sifrious/quain', $scheme, $version),
        $identifier,
    );
}

it('serializes references deterministically across supported domains', function (string $scheme, string $identifier) {
    $reference = concept($scheme, $identifier);

    expect(ConceptReference::deserialize($reference->serialize()))
        ->toEqual($reference)
        ->and($reference->serialize())->toBe(json_encode($reference->toArray(), JSON_UNESCAPED_SLASHES));
})->with([
    'programming language' => ['programming-language', 'algebraic-effects'],
    'architecture' => ['software-architecture', 'event-sourcing'],
    'theory' => ['computer-science-theory', 'curry-howard-correspondence'],
    'user domain' => ['user:mary/gardening', 'succession-planting'],
]);

it('keeps display snapshots separate from identity', function () {
    $reference = concept('software-architecture', 'ports-and-adapters');
    $resolution = new ConceptResolution(
        $reference,
        ConceptResolutionStatus::Available,
        new ConceptSnapshot('Hexagonal architecture', ['short_label' => 'Ports and adapters']),
    );

    expect($resolution->reference)->toBe($reference)
        ->and($resolution->snapshot?->label)->toBe('Hexagonal architecture');
});

it('requires supersession to name a durable replacement', function () {
    $old = concept('software-architecture', 'legacy-name');
    $new = concept('software-architecture', 'current-name');

    $resolution = new ConceptResolution($old, ConceptResolutionStatus::Superseded, supersededBy: $new);

    expect($resolution->supersededBy)->toBe($new);
});

it('rejects empty and whitespace-bearing identity tokens', function () {
    expect(fn () => concept('software architecture', 'ports-and-adapters'))
        ->toThrow(InvalidArgumentException::class);
});
