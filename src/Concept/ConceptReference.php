<?php

namespace Quain\Core\Concept;

use InvalidArgumentException;
use JsonException;

final readonly class ConceptReference
{
    public const CONTRACT_VERSION = 1;

    public function __construct(
        public VocabularySchemeReference $scheme,
        public string $identifier,
        public ?string $version = null,
    ) {
        if (trim($identifier) === '' || preg_match('/\s/', $identifier)) {
            throw new InvalidArgumentException('Concept identifier must be a non-empty token.');
        }

        if ($version !== null && (trim($version) === '' || preg_match('/\s/', $version))) {
            throw new InvalidArgumentException('Concept version must be null or a non-empty token.');
        }
    }

    /** @return array{contract_version: int, scheme: array{owner: string, identifier: string, version: string}, identifier: string, version: ?string} */
    public function toArray(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'scheme' => $this->scheme->toArray(),
            'identifier' => $this->identifier,
            'version' => $this->version,
        ];
    }

    /** @throws JsonException */
    public function serialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** @param array{contract_version: int, scheme: array{owner: string, identifier: string, version: string}, identifier: string, version: ?string} $value */
    public static function fromArray(array $value): self
    {
        if (($value['contract_version'] ?? null) !== self::CONTRACT_VERSION) {
            throw new InvalidArgumentException('Unsupported concept-reference contract version.');
        }

        return new self(
            VocabularySchemeReference::fromArray($value['scheme']),
            $value['identifier'],
            $value['version'],
        );
    }

    /** @throws JsonException */
    public static function deserialize(string $value): self
    {
        $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Concept reference must decode to an object.');
        }

        return self::fromArray($decoded);
    }
}
