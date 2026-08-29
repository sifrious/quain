<?php

namespace Quain\Core;

use InvalidArgumentException;

class CapabilityManifest
{
    /**
     * @param list<CapabilityPort> $inputs
     * @param list<CapabilityPort> $outputs
     * @param list<CapabilityDependency> $dependencies
     * @param list<Readiness> $readiness
     * @param list<Approval> $approvals
     * @param list<CompatibilityConstraint> $compatibility
     * @param list<ExitCriterion> $exitCriteria
     * @param list<VocabularyReference> $vocabulary
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $contractVersion,
        public readonly string $version,
        public readonly array $inputs = [],
        public readonly array $outputs = [],
        public readonly array $dependencies = [],
        public readonly array $readiness = [],
        public readonly Parallelism $parallelism = new Parallelism(),
        public readonly array $approvals = [],
        public readonly array $compatibility = [],
        public readonly array $exitCriteria = [],
        public readonly array $vocabulary = [],
        public readonly ?Deprecation $deprecation = null,
    ) {
        if ($this->id === '') {
            throw new InvalidArgumentException('A capability manifest requires an id.');
        }

        if ($this->name === '') {
            throw new InvalidArgumentException('A capability manifest requires a name.');
        }

        PublishedContracts::accept($this->contractVersion);
    }

    public static function fromArray(array $data): self
    {
        $contractVersion = (string) ($data['contractVersion'] ?? '');

        PublishedContracts::accept($contractVersion);

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            contractVersion: $contractVersion,
            version: (string) ($data['version'] ?? '1'),
            inputs: self::map($data['inputs'] ?? [], CapabilityPort::fromArray(...)),
            outputs: self::map($data['outputs'] ?? [], CapabilityPort::fromArray(...)),
            dependencies: self::map($data['dependencies'] ?? [], CapabilityDependency::fromArray(...)),
            readiness: self::map($data['readiness'] ?? [], Readiness::fromArray(...)),
            parallelism: Parallelism::fromArray($data['parallelism'] ?? null),
            approvals: self::map($data['approvals'] ?? [], Approval::fromArray(...)),
            compatibility: self::map($data['compatibility'] ?? [], CompatibilityConstraint::fromArray(...)),
            exitCriteria: self::map($data['exitCriteria'] ?? [], ExitCriterion::fromArray(...)),
            vocabulary: self::map($data['vocabulary'] ?? [], VocabularyReference::fromArray(...)),
            deprecation: isset($data['deprecation']) ? Deprecation::fromArray($data['deprecation']) : null,
        );
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('Capability manifest JSON is not an object.');
        }

        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'contractVersion' => $this->contractVersion,
            'version' => $this->version,
            'inputs' => array_map(fn (CapabilityPort $port) => $port->toArray(), $this->inputs),
            'outputs' => array_map(fn (CapabilityPort $port) => $port->toArray(), $this->outputs),
            'dependencies' => array_map(fn (CapabilityDependency $dependency) => $dependency->toArray(), $this->dependencies),
            'readiness' => array_map(fn (Readiness $readiness) => $readiness->toArray(), $this->readiness),
            'parallelism' => $this->parallelism->toArray(),
            'approvals' => array_map(fn (Approval $approval) => $approval->toArray(), $this->approvals),
            'compatibility' => array_map(fn (CompatibilityConstraint $constraint) => $constraint->toArray(), $this->compatibility),
            'exitCriteria' => array_map(fn (ExitCriterion $criterion) => $criterion->toArray(), $this->exitCriteria),
            'vocabulary' => array_map(fn (VocabularyReference $reference) => $reference->toArray(), $this->vocabulary),
            'deprecation' => $this->deprecation?->toArray(),
        ], fn (mixed $value) => $value !== null);
    }

    /**
     * @param list<array> $items
     * @return list<object>
     */
    private static function map(array $items, callable $factory): array
    {
        return array_values(array_map($factory, $items));
    }
}
