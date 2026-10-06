<?php

namespace App\Events;

use App\Models\Digest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An editor used "Publish and notify" on a digest.
 */
class DigestPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Digest $digest) {}
}
