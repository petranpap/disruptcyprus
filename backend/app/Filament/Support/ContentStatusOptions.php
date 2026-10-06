<?php

namespace App\Filament\Support;

use App\Enums\ContentStatus;

final class ContentStatusOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (ContentStatus::cases() as $status) {
            $options[$status->value] = __('admin.status.'.$status->value);
        }

        return $options;
    }

    public static function color(ContentStatus|string|null $status): string
    {
        $status = $status instanceof ContentStatus ? $status : ContentStatus::tryFrom((string) $status);

        return match ($status) {
            ContentStatus::Published => 'success',
            ContentStatus::Scheduled => 'info',
            ContentStatus::Archived => 'gray',
            default => 'warning',
        };
    }

    public static function label(ContentStatus|string|null $status): string
    {
        $status = $status instanceof ContentStatus ? $status : ContentStatus::tryFrom((string) $status);

        return $status === null ? '' : __('admin.status.'.$status->value);
    }
}
