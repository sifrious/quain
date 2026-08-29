<?php

use Quain\Core\CapabilityDependency;
use Quain\Core\CapabilityManifest;
use Quain\Core\DependencyOrder;
use Quain\Core\PublishedContracts;

function manifest(string $id, array $dependencies = []): CapabilityManifest
{
    return new CapabilityManifest(
        id: $id,
        name: $id,
        contractVersion: PublishedContracts::MANIFEST_V1,
        version: '1',
        dependencies: array_map(
            fn (array|string $dependency) => CapabilityDependency::fromArray(
                is_string($dependency) ? ['id' => $dependency] : $dependency,
            ),
            $dependencies,
        ),
    );
}

it('orders dependencies deterministically regardless of input order', function () {
    $manifests = [
        manifest('scan', ['index']),
        manifest('index', ['parse']),
        manifest('parse'),
        manifest('report', ['scan', 'index']),
    ];

    $forward = DependencyOrder::resolve($manifests);
    $reversed = DependencyOrder::resolve(array_reverse($manifests));

    expect($forward->ok())->toBeTrue()
        ->and($forward->identities)->toBe(['parse', 'index', 'scan', 'report'])
        ->and($reversed->identities)->toBe($forward->identities);
});

it('breaks ties by identity so equal-ready nodes stay stable', function () {
    $order = DependencyOrder::resolve([
        manifest('zeta'),
        manifest('alpha'),
        manifest('mu', ['alpha', 'zeta']),
    ]);

    expect($order->identities)->toBe(['alpha', 'zeta', 'mu']);
});

it('reports a missing required dependency as data', function () {
    $order = DependencyOrder::resolve([
        manifest('scan', ['missing-index']),
    ]);

    expect($order->ok())->toBeFalse()
        ->and($order->identities)->toBe([])
        ->and($order->issues)->toBe([[
            'code' => 'missing-dependency',
            'id' => 'scan',
            'missing' => 'missing-index',
        ]]);
});

it('records an optional missing dependency without hiding it', function () {
    $order = DependencyOrder::resolve([
        manifest('scan', [['id' => 'pretty-printer', 'optional' => true]]),
    ]);

    expect($order->ok())->toBeTrue()
        ->and($order->identities)->toBe(['scan'])
        ->and($order->issues)->toBe([[
            'code' => 'missing-optional-dependency',
            'id' => 'scan',
            'missing' => 'pretty-printer',
        ]]);
});

it('reports a cycle as data rather than looping', function () {
    $order = DependencyOrder::resolve([
        manifest('a', ['b']),
        manifest('b', ['c']),
        manifest('c', ['a']),
    ]);

    expect($order->ok())->toBeFalse()
        ->and($order->identities)->toBe([])
        ->and($order->issues[0]['code'])->toBe('cyclic-dependency')
        ->and($order->issues[0]['cycle'])->toContain('a')
        ->and($order->issues[0]['cycle'])->toContain('b')
        ->and($order->issues[0]['cycle'])->toContain('c');
});
