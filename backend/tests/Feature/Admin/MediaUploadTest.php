<?php

use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Industry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('stores images uploaded in the admin on the public media disk, so the app can load them', function () {
    Storage::fake('public');
    Storage::fake('local');
    config(['filament.default_filesystem_disk' => 'local']);
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->editor()->create());
    $article = newArticle([], [Industry::factory()->create()]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->set('data.hero', [UploadedFile::fake()->image('hero.jpg', 1200, 800)])
        ->call('save')
        ->assertHasNoFormErrors();

    $media = $article->refresh()->getFirstMedia('hero');
    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('public')
        ->and($media->getUrl())->toStartWith('/storage/');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
});
