<?php

use App\Enums\UserRole;
use App\Filament\Pages\TaxonomyTree;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Industry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin()->create());
});

it('lets admins create an editor who can then sign in to the admin', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Maria Editor',
            'email' => ' Maria@Example.com ',
            'role' => 'editor',
            'locale' => 'el',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $editor = User::query()->where('email', 'maria@example.com')->sole();
    expect($editor->role)->toBe(UserRole::Editor)
        ->and($editor->locale)->toBe('el')
        ->and($editor->email_verified_at)->not->toBeNull()
        ->and(Hash::check('secret123', (string) $editor->password))->toBeTrue()
        ->and($editor->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('validates new accounts: password strength, confirmation and unique email', function () {
    reader(['email' => 'taken@example.com']);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'taken@example.com', 'role' => 'admin', 'locale' => 'en', 'password' => 'short', 'password_confirmation' => 'other'])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique', 'password']);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'new@example.com', 'role' => 'admin', 'locale' => 'en', 'password' => ''])
        ->call('create')
        ->assertHasFormErrors(['password' => 'required']);
});

it('lets admins change name, email, role and password, keeping the password when left empty', function () {
    $user = User::factory()->editor()->create(['password' => 'oldpass123']);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['name' => 'Renamed', 'email' => 'renamed@example.com', 'role' => 'admin', 'password' => '', 'password_confirmation' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->name)->toBe('Renamed')
        ->and($user->email)->toBe('renamed@example.com')
        ->and($user->role)->toBe(UserRole::Admin)
        ->and(Hash::check('oldpass123', (string) $user->password))->toBeTrue();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['password' => 'newpass456', 'password_confirmation' => 'newpass456'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('newpass456', (string) $user->refresh()->password))->toBeTrue();
});

it('keeps deleted (anonymized) accounts read-only', function () {
    $user = reader();
    $user->delete();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormFieldDisabled('email')
        ->assertFormFieldDisabled('password');
});

it('shows the structure tree with sections, groups and industries', function () {
    section('news');
    Industry::factory()->create(['name' => ['en' => 'TreeTech', 'el' => 'TreeTech'], 'group' => 'deep_tech']);

    Livewire::test(TaxonomyTree::class)
        ->assertOk()
        ->assertSee('Sections')
        ->assertSee('/news')
        ->assertSee('Deep Tech')
        ->assertSee('TreeTech');
});
