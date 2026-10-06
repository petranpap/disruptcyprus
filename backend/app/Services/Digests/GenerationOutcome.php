<?php

namespace App\Services\Digests;

enum GenerationOutcome: string
{
    case Created = 'created';
    case Refreshed = 'refreshed';
    case SkippedExisting = 'skipped_existing';
    case SkippedEdited = 'skipped_edited';
    case SkippedPublished = 'skipped_published';
}
