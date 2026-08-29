<?php

namespace Quain\Core\Concept;

use InvalidArgumentException;

final readonly class VocabularySchemeReference
{
    public function __construct(
        public string $owner,
        public string $identifier,
        public string $version,
    ) {
        self::requireToken($owner, 'owner');
        self::requireToken($identifier, 'identifier');
        self::requireToken($version, 'version');
    }

    /** @return array{owner: string, identifier: string, version: string} */
    public function toArray(): array
    {
        return [
            'owner' => $this->owner,
            'identifier' => $this->identifier,
            'version' => $this->version,
        ];
    }

    /** @param array{owner: string, identifier: string, version: string} $value */
    public static function fromArray(array $value): self
    {
        return new self($value['owner'], $value['identifier'], $value['version']);
    }

    private static function requireToken(string $value, string $field): void
    {
        if (trim($value) === '' || preg_match('/\s/', $value)) {
            throw new InvalidArgumentException("Vocabulary scheme {$field} must be a non-empty token.");
        }
    }
}
