<?php

namespace App\Filament\Support;

use App\Models\Article;
use App\Models\Event;
use App\Models\Industry;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

/**
 * "Industries" multi-select plus "Primary industry" chosen among them, stored on the
 * article_industry / event_industry pivots (is_primary).
 */
final class IndustryFields
{
    /**
     * @return list<Select>
     */
    public static function make(): array
    {
        return [
            Select::make('industry_ids')
                ->label(__('admin.common.industries'))
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->options(fn () => self::options()),
            Select::make('primary_industry_id')
                ->label(__('admin.common.primary_industry'))
                ->required()
                ->options(fn (Get $get) => array_intersect_key(self::options(), array_flip(array_map('intval', (array) $get('industry_ids')))))
                ->in(fn (Get $get) => array_map('intval', (array) $get('industry_ids')))
                ->validationMessages(['in' => __('admin.validation.primary_in_industries')]),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return Industry::query()->active()->ordered()->get()
            ->mapWithKeys(fn (Industry $industry) => [$industry->id => $industry->name])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(Article|Event $record, array $data): array
    {
        $record->loadMissing('industries');

        $data['industry_ids'] = $record->industries->modelKeys();
        $data['primary_industry_id'] = $record->primaryIndustry()?->id;

        return $data;
    }

    /**
     * Removes the virtual fields from the form data and returns them separately.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array{is_primary: bool}>}
     */
    public static function extract(array $data): array
    {
        $ids = array_map('intval', (array) ($data['industry_ids'] ?? []));
        $primary = (int) ($data['primary_industry_id'] ?? 0);
        unset($data['industry_ids'], $data['primary_industry_id']);

        $pivot = [];
        foreach ($ids as $id) {
            $pivot[$id] = ['is_primary' => $id === $primary];
        }

        return [$data, $pivot];
    }
}
