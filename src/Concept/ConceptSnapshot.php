<?php

namespace Quain\Core\Concept;

use InvalidArgumentException;

final readonly class ConceptSnapshot
{
    /** @param array<string, scalar|null> $attributes */
    public function __construct(
        public string $label,
        public array $attributes = [],
    ) {
        if (trim($label) === '') {
            throw new InvalidArgumentException('Concept snapshot label cannot be empty.');
        }
    }
}
