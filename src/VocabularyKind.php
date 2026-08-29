<?php

namespace Quain\Core;

use InvalidArgumentException;

class VocabularyKind
{
    public const ALGORITHM = 'algorithm';

    public const ARCHITECTURE = 'architecture';

    public const DATA_STRUCTURE = 'data-structure';

    public const DESIGN_PATTERN = 'design-pattern';

    public const DISCIPLINE = 'discipline';

    public const VALUE = 'value';

    public const IDEAL = 'ideal';

    public static function all(): array
    {
        return [
            self::ALGORITHM,
            self::ARCHITECTURE,
            self::DATA_STRUCTURE,
            self::DESIGN_PATTERN,
            self::DISCIPLINE,
            self::VALUE,
            self::IDEAL,
        ];
    }

    public static function assert(string $kind): string
    {
        if (! in_array($kind, self::all(), true)) {
            throw new InvalidArgumentException("Unknown vocabulary kind [{$kind}].");
        }

        return $kind;
    }
}
