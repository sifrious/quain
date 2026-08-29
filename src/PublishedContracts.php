<?php

namespace Quain\Core;

class PublishedContracts
{
    public const MANIFEST_V0 = 'quain.capability-manifest.0';

    public const MANIFEST_V1 = 'quain.capability-manifest.1';

    public const MANIFEST_V1_NOTES = 'quain.capability-manifest.1.notes';

    public const CHANGE_COMPATIBLE = 'compatible';

    public const CHANGE_ADDITIVE = 'additive';

    public const CHANGE_DEPRECATED = 'deprecated';

    public const CHANGE_BREAKING = 'breaking';

    public static function current(): string
    {
        return self::MANIFEST_V1;
    }

    /**
     * Contract versions this package will consume without a coordinated upgrade.
     *
     * @return list<array{id: string, change: string, deprecation: ?array}>
     */
    public static function describe(): array
    {
        return array_values(array_map(
            fn (string $id) => [
                'id' => $id,
                'change' => self::classify($id),
                'deprecation' => self::deprecation($id)?->toArray(),
            ],
            self::supported(),
        ));
    }

    /** @return list<string> */
    public static function supported(): array
    {
        return [
            self::MANIFEST_V1,
            self::MANIFEST_V1_NOTES,
            self::MANIFEST_V0,
        ];
    }

    public static function classify(string $contractVersion): string
    {
        return match ($contractVersion) {
            self::MANIFEST_V1 => self::CHANGE_COMPATIBLE,
            self::MANIFEST_V1_NOTES => self::CHANGE_ADDITIVE,
            self::MANIFEST_V0 => self::CHANGE_DEPRECATED,
            default => self::CHANGE_BREAKING,
        };
    }

    public static function deprecation(string $contractVersion): ?Deprecation
    {
        if ($contractVersion !== self::MANIFEST_V0) {
            return null;
        }

        return new Deprecation(
            replacement: self::MANIFEST_V1,
            firstDeprecatedIn: self::MANIFEST_V1,
            removalCondition: 'Removed when no published consumer still declares support for quain.capability-manifest.0.',
        );
    }

    public static function accept(string $contractVersion): string
    {
        if (! in_array($contractVersion, self::supported(), true)) {
            throw new UnsupportedContract(
                requested: $contractVersion,
                supported: self::supported(),
                change: self::classify($contractVersion),
            );
        }

        return $contractVersion;
    }
}
