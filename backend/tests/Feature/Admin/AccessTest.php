<?php

use App\Filament\Widgets\ContentPerWeekChart;
use App\Filament\Widgets\DigestsDue;
use App\Filament\Widgets\FollowedIndustries;
use App\Filament\Widgets\NewUsersChart;
use App\Filament\Widgets\OverviewStats;
use App\Filament\Widgets\TopArticles;
use App\Models\Digest;
use App\Models\User;

dataset('editorial pages', ['/backend', '/backend/articles', '/backend/events', '/backend/digests', '/backend/authors', '/backend/push-campaigns', '/backend/notification-log']);
dataset('admin-only pages', ['/backend/users', '/backend/users/create', '/backend/industries', '/backend/sections', '/backend/structure', '/backend/waitlist']);

it('keeps readers out of the panel by signing them out and showing the login', function () {
    $this->actingAs(reader())->get('/backend')->assertRedirect('/backend/login');
    $this->assertGuest();
});

it('redirects guests to the login page', function () {
    $this->get('/backend/articles')->assertRedirect('/backend/login');
});

it('lets editors open editorial pages', function (string $url) {
    $this->actingAs(User::factory()->editor()->create())->get($url)->assertOk();
})->with('editorial pages');

it('keeps editors out of settings pages', function (string $url) {
    $this->actingAs(User::factory()->editor()->create())->get($url)->assertForbidden();
})->with('admin-only pages');

it('lets admins open every page', function (string $url) {
    $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
})->with('editorial pages', 'admin-only pages');

it('renders every dashboard widget with content', function (string $widget) {
    Filament\Facades\Filament::setCurrentPanel('admin');
    newArticle();
    newEvent(['starts_at' => now()->addDays(3)]);
    Digest::factory()->create(['status' => 'draft', 'published_at' => null]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test($widget)->assertOk();
})->with([
    OverviewStats::class,
    ContentPerWeekChart::class,
    NewUsersChart::class,
    DigestsDue::class,
    TopArticles::class,
    FollowedIndustries::class,
]);

it('switches the admin language for the signed-in editor', function () {
    $editor = User::factory()->editor()->create(['locale' => 'el']);

    $this->actingAs($editor)->from('/backend/articles')->get('/backend-language/en')->assertRedirect('/backend/articles');
    expect($editor->refresh()->locale)->toBe('en');

    $this->actingAs($editor)->get('/backend-language/fr')->assertNotFound();
});
