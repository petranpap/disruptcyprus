<?php

namespace App\Filament\Resources\PushCampaigns;

use App\Enums\PushAudience;
use App\Enums\PushCampaignStatus;
use App\Filament\Resources\PushCampaigns\Pages\CreatePushCampaign;
use App\Filament\Resources\PushCampaigns\Pages\EditPushCampaign;
use App\Filament\Resources\PushCampaigns\Pages\ListPushCampaigns;
use App\Filament\Support\IndustryFields;
use App\Filament\Support\Translatable;
use App\Jobs\SendPushCampaign;
use App\Models\PushCampaign;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\RateLimiter;
use UnitEnum;

class PushCampaignResource extends Resource
{
    /** Manual campaigns per staff member per hour. */
    public const SENDS_PER_HOUR = 3;

    protected static ?string $model = PushCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'engagement';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('admin.push.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.push.plural');
    }

    /**
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return collect(PushAudience::cases())->mapWithKeys(fn (PushAudience $audience) => [$audience->value => __('admin.push.audiences.'.$audience->value)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Callout::make(__('admin.push.stub_notice'))->warning()->columnSpanFull(),
            Section::make()->columnSpanFull()->schema([
                Translatable::tabs(fn (string $locale) => [
                    TextInput::make("title.{$locale}")->label(__('admin.common.title'))->required()->maxLength(60),
                    Textarea::make("body.{$locale}")->label(__('admin.push.body'))->required()->rows(2)->maxLength(160),
                ]),
                TextInput::make('url')
                    ->label(__('admin.push.url'))
                    ->helperText(__('admin.push.url_help'))
                    ->regex('#^/[A-Za-z0-9/_\-?=&.%]*$#')
                    ->maxLength(512),
                Radio::make('audience')->label(__('admin.push.audience'))->options(self::audienceOptions())->default('all')->required()->live(),
                Select::make('industry_ids')
                    ->label(__('admin.common.industries'))
                    ->multiple()
                    ->options(fn () => IndustryFields::options())
                    ->visible(fn (Get $get) => $get('audience') === PushAudience::Industries->value)
                    ->required(fn (Get $get) => $get('audience') === PushAudience::Industries->value),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('author'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('admin.common.title'))->wrap(),
                TextColumn::make('audience')->label(__('admin.push.audience'))->badge()->color('gray')
                    ->formatStateUsing(fn (PushAudience $state) => __('admin.push.audiences.'.$state->value)),
                TextColumn::make('status')->label(__('admin.common.status'))->badge()
                    ->formatStateUsing(fn (PushCampaignStatus $state) => __('admin.push.statuses.'.$state->value))
                    ->color(fn (PushCampaignStatus $state) => match ($state) {
                        PushCampaignStatus::Sent => 'success',
                        PushCampaignStatus::Queued => 'info',
                        PushCampaignStatus::Failed => 'danger',
                        PushCampaignStatus::Draft => 'warning',
                    }),
                TextColumn::make('recipients_count')->label(__('admin.push.recipients'))->numeric(),
                TextColumn::make('failures_count')->label(__('admin.push.failures'))->numeric(),
                TextColumn::make('sent_at')->label(__('admin.push.sent_at'))->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('author.name')->label(__('admin.push.author')),
            ])
            ->recordActions([self::sendAction(), EditAction::make()]);
    }

    public static function sendAction(): Action
    {
        return Action::make('send')
            ->label(__('admin.push.send'))
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->visible(fn (PushCampaign $record) => $record->isDraft())
            ->requiresConfirmation()
            ->modalDescription(fn (PushCampaign $record) => __('admin.push.send_confirm', ['count' => $record->audienceQuery()->count()]))
            ->action(function (PushCampaign $record): void {
                $key = 'push-campaigns:'.auth()->id();

                if (RateLimiter::tooManyAttempts($key, self::SENDS_PER_HOUR)) {
                    Notification::make()->danger()
                        ->title(__('admin.push.rate_limited', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]))
                        ->send();

                    return;
                }

                RateLimiter::hit($key, 3600);

                $record->forceFill(['status' => PushCampaignStatus::Queued, 'queued_at' => now()])->save();
                SendPushCampaign::dispatch($record);

                Notification::make()->success()->title(__('admin.push.sent'))->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushCampaigns::route('/'),
            'create' => CreatePushCampaign::route('/create'),
            'edit' => EditPushCampaign::route('/{record}/edit'),
        ];
    }
}
