<?php

namespace Quain\Core\Concept;

enum ConceptResolutionStatus: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';
    case Tombstoned = 'tombstoned';
    case Superseded = 'superseded';
    case Unauthorized = 'unauthorized';
}
