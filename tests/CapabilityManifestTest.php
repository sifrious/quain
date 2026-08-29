<?php

use Quain\Core\CapabilityManifest;
use Quain\Core\PublishedContracts;
use Quain\Core\VocabularyKind;
use Quain\Core\VocabularyReference;

it('loads a versioned capability manifest with composition metadata', function () {
    $manifest = CapabilityManifest::fromArray(repositoryScanPayload());

    expect($manifest->id)->toBe('repository-scan')
        ->and($manifest->name)->toBe('Repository scan')
        ->and($manifest->contractVersion)->toBe(PublishedContracts::MANIFEST_V1)
        ->and($manifest->version)->toBe('1')
        ->and($manifest->inputs[0]->name)->toBe('path')
        ->and($manifest->outputs[0]->name)->toBe('inventory')
        ->and($manifest->readiness[0]->condition)->toBe('path-is-readable')
        ->and($manifest->parallelism->allowed)->toBeTrue()
        ->and($manifest->parallelism->max)->toBe(4)
        ->and($manifest->approvals[0]->role)->toBe('operator')
        ->and($manifest->compatibility[0]->id)->toBe('posix-filesystem')
        ->and($manifest->exitCriteria[0]->statement)->toContain('inventory')
        ->and(array_map(fn (VocabularyReference $reference) => $reference->kind, $manifest->vocabulary))
        ->toBe(VocabularyKind::all());
});

it('keeps capability version distinct from the contract version', function () {
    $payload = repositoryScanPayload();
    $payload['version'] = '3';
    $payload['contractVersion'] = PublishedContracts::MANIFEST_V1;

    $manifest = CapabilityManifest::fromArray($payload);

    expect($manifest->version)->toBe('3')
        ->and($manifest->contractVersion)->toBe(PublishedContracts::MANIFEST_V1)
        ->and($manifest->version)->not->toBe($manifest->contractVersion);
});

it('requires identity fields', function () {
    $payload = repositoryScanPayload();
    unset($payload['name']);

    expect(fn () => CapabilityManifest::fromArray($payload))
        ->toThrow(InvalidArgumentException::class, 'name');
});

it('exposes no Landing, HTTP, queue, or provider types from public contracts', function () {
    $sources = array_merge(
        glob(__DIR__.'/../src/*.php') ?: [],
        glob(__DIR__.'/../src/**/*.php') ?: [],
    );

    $forbidden = [
        'Illuminate\\',
        'App\\Models',
        'App\\Http',
        'Eloquent',
        'Livewire',
        'Blade',
        'OpenAI',
        'Anthropic',
        'Queueable',
    ];

    foreach ($sources as $path) {
        $contents = file_get_contents($path);

        foreach ($forbidden as $token) {
            expect($contents)->not->toContain($token);
        }
    }

    $declared = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);

    expect($declared['require'])->not->toHaveKey('illuminate/support')
        ->and(implode(' ', array_keys($declared['require'])))->not->toContain('laravel');
});
