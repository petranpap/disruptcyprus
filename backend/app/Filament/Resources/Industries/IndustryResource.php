<?php

namespace App\Filament\Resources\Industries;

use App\Enums\IndustryGroup;
use App\Filament\Resources\Industries\Pages\CreateIndustry;
use App\Filament\Resources\Industries\Pages\EditIndustry;
use App\Filament\Resources\Industries\Pages\ListIndustries;
use App\Filament\Support\Translatable;
use App\Models\Industry;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'taxonomy';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('admin.industries.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.industries.plural');
    }

    /**
     * @return array<string, string>
     */
    public static function groupOptions(): array
    {
        return collect(IndustryGroup::cases())->mapWithKeys(fn (IndustryGroup $group) => [$group->value => __('admin.industries.groups.'.$group->value)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                Translatable::tabs(fn (string $locale) => [
                    TextInput::make("name.{$locale}")->label(__('admin.common.name'))->required()->maxLength(80),
                ]),
                TextInput::make('slug')->label(__('admin.common.slug'))->required()->alphaDash()->maxLength(64)->unique(ignoreRecord: true),
                Select::make('group')->label(__('admin.common.group'))->options(self::groupOptions())->required(),
                ColorPicker::make('color')->label(__('admin.common.color'))->required()->regex('/^#[0-9A-Fa-f]{6}$/'),
                TextInput::make('sort_order')->label(__('admin.common.sort_order'))->numeric()->default(0),
                Toggle::make('is_active')->label(__('admin.common.active'))->default(true),
                SpatieMediaLibraryFileUpload::make('image')->label(__('admin.common.image'))->collection(Industry::IMAGE_COLLECTION)->image()->maxSize(4096)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('followers'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label(__('admin.common.name'))->searchable(query: fn (Builder $query, string $search) => $query->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%')),
                TextColumn::make('slug')->label(__('admin.common.slug'))->color('gray'),
                TextColumn::make('group')->label(__('admin.common.group'))->badge()->formatStateUsing(fn (IndustryGroup $state) => __('admin.industries.groups.'.$state->value)),
                TextColumn::make('followers_count')->label(__('admin.industries.followers'))->numeric()->sortable(),
                IconColumn::make('is_active')->label(__('admin.common.active'))->boolean(),
            ])
            ->filters([SelectFilter::make('group')->label(__('admin.common.group'))->options(self::groupOptions())])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndustries::route('/'),
            'create' => CreateIndustry::route('/create'),
            'edit' => EditIndustry::route('/{record}/edit'),
        ];
    }
}
