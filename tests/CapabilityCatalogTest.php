<?php

use Quain\Core\CapabilityCatalog;
use Quain\Core\CapabilityManifest;
use Quain\Core\PublishedContracts;

function catalogFixture(): CapabilityCatalog
{
    $scan = CapabilityManifest::fromArray(repositoryScanPayload());
    $lint = CapabilityManifest::fromArray([
        'id' => 'lint',
        'name' => 'Lint repository',
        'version' => '1',
        'contractVersion' => PublishedContracts::MANIFEST_V1,
        'compatibility' => [[
            'id' => 'posix-filesystem',
            'range' => '>=1',
        ]],
        'vocabulary' => [[
            'kind' => 'discipline',
            'id' => 'landing:disciplines/code-quality',
        ]],
    ]);
    $publish = CapabilityManifest::fromArray([
        'id' => 'publish-report',
        'name' => 'Publish report',
        'version' => '1',
        'contractVersion' => PublishedContracts::MANIFEST_V1,
        'compatibility' => [[
            'id' => 'http-api',
            'range' => '^2',
        ]],
    ]);

    return new CapabilityCatalog([$publish, $scan, $lint]);
}

it('lists and inspects manifests without mutating canonical definitions', function () {
    $catalog = catalogFixture();
    $snapshot = array_map(fn (CapabilityManifest $manifest) => $manifest->toArray(), $catalog->all());

    expect(array_map(fn (CapabilityManifest $manifest) => $manifest->id, $catalog->all()))
        ->toBe(['lint', 'publish-report', 'repository-scan'])
        ->and($catalog->inspect('repository-scan')?->name)->toBe('Repository scan')
        ->and($catalog->inspect('missing'))->toBeNull();

    $after = array_map(fn (CapabilityManifest $manifest) => $manifest->toArray(), $catalog->all());

    expect($after)->toBe($snapshot);
});

it('searches manifests by id name and vocabulary reference in read-only mode', function () {
    $catalog = catalogFixture();

    expect(array_map(fn (CapabilityManifest $manifest) => $manifest->id, $catalog->search('repo')))
        ->toBe(['lint', 'publish-report', 'repository-scan'])
        ->and(array_map(fn (CapabilityManifest $manifest) => $manifest->id, $catalog->search('code-quality')))
        ->toBe(['lint'])
        ->and(array_map(fn (CapabilityManifest $manifest) => $manifest->id, $catalog->search('design-pattern')))
        ->toBe(['repository-scan']);
});

it('queries compatible capabilities without mutating manifests', function () {
    $catalog = catalogFixture();
    $before = array_map(fn (CapabilityManifest $manifest) => $manifest->toArray(), $catalog->all());

    expect(array_map(fn (CapabilityManifest $manifest) => $manifest->id, $catalog->compatibleWith('posix-filesystem')))
        ->toBe(['lint', 'repository-scan'])
        ->and(array_map(fn (CapabilityManifest $manifest) => $manifest->id, $catalog->compatibleWith('http-api', '^2')))
        ->toBe(['publish-report'])
        ->and($catalog->compatibleWith('http-api', '^1'))->toBe([]);

    $after = array_map(fn (CapabilityManifest $manifest) => $manifest->toArray(), $catalog->all());

    expect($after)->toBe($before);
});
