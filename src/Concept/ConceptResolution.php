<?php

namespace Quain\Core\Concept;

use InvalidArgumentException;

final readonly class ConceptResolution
{
    public function __construct(
        public ConceptReference $reference,
        public ConceptResolutionStatus $status,
        public ?ConceptSnapshot $snapshot = null,
        public ?ConceptReference $supersededBy = null,
    ) {
        if ($status === ConceptResolutionStatus::Available && $snapshot === null) {
            throw new InvalidArgumentException('Available concepts require a display snapshot.');
        }

        if ($status === ConceptResolutionStatus::Superseded && $supersededBy === null) {
            throw new InvalidArgumentException('Superseded concepts require a replacement reference.');
        }

        if ($status !== ConceptResolutionStatus::Superseded && $supersededBy !== null) {
            throw new InvalidArgumentException('Only superseded concepts may name a replacement.');
        }
    }
}
