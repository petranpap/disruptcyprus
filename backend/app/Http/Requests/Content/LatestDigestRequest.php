<?php

namespace App\Http\Requests\Content;

class LatestDigestRequest extends IndexDigestsRequest
{
    protected function kindRequirement(): string
    {
        return 'required';
    }
}
