<?php

namespace App\Filament\Pages;

use App\Enums\IndustryGroup;
use App\Filament\Resources\Industries\IndustryResource;
use App\Filament\Resources\Sections\SectionResource;
use App\Models\Industry;
use App\Models\Section;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Read-only overview of the content structure as a top-down tree: sections, then industry groups and their
 * industries, each linking to its edit page. Admins only, like the resources it summarises.
 */
class TaxonomyTree extends Page
{
    protected string $view = 'filament.pages.taxonomy-tree';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static string|UnitEnum|null $navigationGroup = 'taxonomy';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'structure';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.tree.title');
    }

    public function getTitle(): string
    {
        return __('admin.tree.title');
    }

    public function getSubheading(): string
    {
        return __('admin.tree.subtitle');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $locale = app()->getLocale();

        $sections = Section::query()->withCount('articles')->orderBy('sort_order')->get()
            ->map(fn (Section $section) => [
                'name' => $section->getTranslation('name', $locale),
                'slug' => $section->slug,
                'articles' => (int) $section->getAttribute('articles_count'),
                'has_articles' => (bool) $section->has_articles,
                'url' => SectionResource::getUrl('edit', ['record' => $section]),
            ]);

        $industries = Industry::query()->withCount(['followers', 'articles'])->orderBy('sort_order')->get()->groupBy(fn (Industry $industry) => $industry->group->value);

        $groups = collect(IndustryGroup::cases())->map(fn (IndustryGroup $group) => [
            'name' => __('admin.industries.groups.'.$group->value),
            'industries' => ($industries[$group->value] ?? collect())->map(fn (Industry $industry) => [
                'name' => $industry->getTranslation('name', $locale),
                'color' => $industry->color,
                'active' => $industry->is_active,
                'followers' => (int) $industry->getAttribute('followers_count'),
                'articles' => (int) $industry->getAttribute('articles_count'),
                'url' => IndustryResource::getUrl('edit', ['record' => $industry]),
            ])->values(),
        ]);

        return ['sections' => $sections, 'groups' => $groups];
    }
}
