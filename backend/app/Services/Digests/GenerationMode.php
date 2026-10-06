<?php

namespace App\Services\Digests;

enum GenerationMode
{
    /** Create the draft if the period has none; otherwise leave everything alone (scheduler). */
    case CreateOnly;

    /** Also rebuild an existing draft, unless an editor has edited it. */
    case RefreshUnedited;

    /** Rebuild an existing draft even if edited ("Regenerate draft" in the admin, after confirmation). */
    case Overwrite;
}
