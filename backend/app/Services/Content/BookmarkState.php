<?php

namespace App\Services\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Request-scoped cache of which items the current user has saved, filled in one query per type
 * so card resources can expose `is_bookmarked` without N+1 queries.
 */
class BookmarkState
{
    /** @var array<string, true> */
    private array $saved = [];

    /** @var array<string, true> */
    private array $checked = [];

    /**
     * @param  iterable<Model>  $items
     */
    public function prime(?User $user, iterable $items): void
    {
        if ($user === null) {
            return;
        }

        $idsByType = [];

        foreach ($items as $item) {
            $key = $this->key($item);

            if (! isset($this->checked[$key])) {
                $idsByType[$item->getMorphClass()][] = $item->getKey();
                $this->checked[$key] = true;
            }
        }

        foreach ($idsByType as $type => $ids) {
            $user->bookmarks()
                ->where('bookmarkable_type', $type)
                ->whereIn('bookmarkable_id', $ids)
                ->pluck('bookmarkable_id')
                ->each(function (int $id) use ($type): void {
                    $this->saved["{$type}:{$id}"] = true;
                });
        }
    }

    public function has(?User $user, Model $item): bool
    {
        if ($user === null) {
            return false;
        }

        $key = $this->key($item);

        if (! isset($this->checked[$key])) {
            $this->prime($user, [$item]);
        }

        return isset($this->saved[$key]);
    }

    private function key(Model $item): string
    {
        return $item->getMorphClass().':'.$item->getKey();
    }
}
