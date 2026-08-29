<?php

namespace Quain\Core;

class CapabilityCatalog
{
    /** @var array<string, CapabilityManifest> */
    private array $byId = [];

    /**
     * @param list<CapabilityManifest> $manifests
     */
    public function __construct(array $manifests)
    {
        foreach ($manifests as $manifest) {
            $this->byId[$manifest->id] = $manifest;
        }

        ksort($this->byId);
    }

    /**
     * @return list<CapabilityManifest>
     */
    public function all(): array
    {
        return array_values($this->byId);
    }

    public function inspect(string $id): ?CapabilityManifest
    {
        return $this->byId[$id] ?? null;
    }

    /**
     * @return list<CapabilityManifest>
     */
    public function search(string $query): array
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return $this->all();
        }

        $matches = array_filter($this->byId, function (CapabilityManifest $manifest) use ($needle): bool {
            if (str_contains(mb_strtolower($manifest->id), $needle) || str_contains(mb_strtolower($manifest->name), $needle)) {
                return true;
            }

            foreach ($manifest->vocabulary as $reference) {
                if (str_contains(mb_strtolower($reference->id), $needle) || str_contains(mb_strtolower($reference->kind), $needle)) {
                    return true;
                }
            }

            return false;
        });

        return array_values($matches);
    }

    /**
     * @return list<CapabilityManifest>
     */
    public function compatibleWith(string $id, ?string $range = null): array
    {
        $matches = array_filter($this->byId, function (CapabilityManifest $manifest) use ($id, $range): bool {
            foreach ($manifest->compatibility as $constraint) {
                if ($constraint->id !== $id) {
                    continue;
                }

                if ($range === null || $constraint->range === $range) {
                    return true;
                }
            }

            return false;
        });

        return array_values($matches);
    }
}
