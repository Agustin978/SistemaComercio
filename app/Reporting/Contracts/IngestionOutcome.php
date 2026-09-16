<?php

namespace App\Reporting\Contracts;

enum IngestionOutcome: string
{
    case Created = 'created';
    case Duplicate = 'duplicate';
    case Divergent = 'divergent';
    case Rejected = 'rejected';
}
