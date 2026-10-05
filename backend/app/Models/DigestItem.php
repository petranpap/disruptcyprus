<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

#[Fillable(['itemable_type', 'itemable_id', 'position', 'editor_note'])]
class DigestItem extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['editor_note'];

    /**
     * @return BelongsTo<Digest, $this>
     */
    public function digest(): BelongsTo
    {
        return $this->belongsTo(Digest::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
