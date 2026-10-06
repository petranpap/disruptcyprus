<?php

namespace App\Filament\Resources\Digests\Schemas;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Filament\Resources\Digests\Pages\EditDigest;
use App\Filament\Support\DigestItemOptions;
use App\Filament\Support\Translatable;
use App\Models\Digest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DigestForm
{
    /**
     * Kind → cadences the product supports.
     *
     * @var array<string, list<string>>
     */
    public const SUPPORTED = [
        'news' => ['daily', 'monthly'],
        'events' => ['weekly', 'monthly'],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->visibleOn('create')->columns(3)->columnSpanFull()->schema([
                Select::make('kind')
                    ->label(__('admin.digests.kind'))
                    ->options(self::kindOptions())
                    ->required()
                    ->live(),
                Select::make('cadence')
                    ->label(__('admin.digests.cadence'))
                    ->options(fn (Get $get) => array_intersect_key(self::cadenceOptions(), array_flip(self::SUPPORTED[$get('kind')] ?? [])))
                    ->required(),
                DatePicker::make('reference_date')
                    ->label(__('admin.digests.period_reference'))
                    ->helperText(__('admin.digests.period_reference_help'))
                    ->default(now(config('app.business_timezone'))->toDateString())
                    ->required(),
            ]),
            Section::make()->visibleOn('edit')->columnSpanFull()->schema([
                Text::make(fn (?Digest $record) => $record === null ? '' : sprintf(
                    '%s · %s · %s → %s%s',
                    __('admin.digests.kinds.'.$record->kind->value),
                    __('admin.digests.cadences.'.$record->cadence->value),
                    $record->period_start->format('d M Y'),
                    $record->period_end->format('d M Y'),
                    $record->edited_at !== null ? ' · '.__('admin.digests.edited') : '',
                )),
                Translatable::tabs(fn (string $locale) => [
                    TextInput::make("title.{$locale}")->label(__('admin.common.title'))->required()->maxLength(200),
                    Textarea::make("intro.{$locale}")->label(__('admin.digests.intro'))->rows(3)->maxLength(1000),
                ]),
            ]),
            Section::make(__('admin.digests.items'))->visibleOn('edit')->columnSpanFull()->schema([
                Repeater::make('items')
                    ->hiddenLabel()
                    ->relationship('items')
                    ->orderColumn('position')
                    ->reorderable()
                    ->collapsible()
                    ->addActionLabel(__('admin.digests.add_item'))
                    ->itemLabel(fn (array $state, EditDigest $livewire) => filled($state['itemable_id'] ?? null)
                        ? DigestItemOptions::label($livewire->getDigest(), $state['itemable_id'])
                        : null)
                    ->schema([
                        Select::make('itemable_id')
                            ->label(fn (EditDigest $livewire) => $livewire->getDigest()->kind === DigestKind::News ? __('admin.digests.item_article') : __('admin.digests.item_event'))
                            ->required()
                            ->searchable()
                            ->distinct()
                            ->options(fn (EditDigest $livewire) => DigestItemOptions::recent($livewire->getDigest()))
                            ->getSearchResultsUsing(fn (string $search, EditDigest $livewire) => DigestItemOptions::search($livewire->getDigest(), $search))
                            ->getOptionLabelUsing(fn ($value, EditDigest $livewire) => DigestItemOptions::label($livewire->getDigest(), $value)),
                        Translatable::tabs(fn (string $locale) => [
                            Textarea::make("editor_note.{$locale}")->label(__('admin.digests.editor_note'))->rows(2)->maxLength(500),
                        ], 'note'),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, EditDigest $livewire) => [
                        ...$data,
                        'itemable_type' => $livewire->getDigest()->itemMorphType(),
                    ]),
            ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function kindOptions(): array
    {
        return collect(DigestKind::cases())->mapWithKeys(fn (DigestKind $kind) => [$kind->value => __('admin.digests.kinds.'.$kind->value)])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function cadenceOptions(): array
    {
        return collect(DigestCadence::cases())->mapWithKeys(fn (DigestCadence $cadence) => [$cadence->value => __('admin.digests.cadences.'.$cadence->value)])->all();
    }
}
