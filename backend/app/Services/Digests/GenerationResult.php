<?php

namespace App\Services\Digests;

use App\Models\Digest;

final readonly class GenerationResult
{
    public function __construct(public GenerationOutcome $outcome, public Digest $digest) {}
}
