<?php

namespace App\Models;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use Database\Factories\DigestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['kind', 'cadence', 'period_start', 'period_end', 'slug', 'title', 'intro', 'status', 'published_at', 'generated_automatically', 'edited_at'])]
class Digest extends Model
{
    /** @use HasFactory<DigestFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'intro'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => DigestKind::class,
            'cadence' => DigestCadence::class,
            'status' => DigestStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'published_at' => 'datetime',
            'generated_automatically' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<DigestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DigestItem::class)->orderBy('position');
    }

    /**
     * Morph type of the items this digest holds: news digests list articles, events digests list events.
     */
    public function itemMorphType(): string
    {
        return $this->kind === DigestKind::News ? 'article' : 'event';
    }

    public function isDraft(): bool
    {
        return $this->status === DigestStatus::Draft;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', DigestStatus::Published)->where('published_at', '<=', now());
    }
}
