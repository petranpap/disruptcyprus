<?php

use App\Models\PushCampaign;
use App\Models\User;
use App\Notifications\CampaignNotification;
use Illuminate\Support\Str;

function inboxItem(User $user, array $data = [], ?string $readAt = null, string $createdAt = 'now'): string
{
    $id = (string) Str::uuid();
    $user->notifications()->create([
        'id' => $id,
        'type' => CampaignNotification::class,
        'data' => ['type' => 'campaign', 'title' => 'Title', 'body' => 'Body', 'url' => '/events', ...$data],
        'read_at' => $readAt,
        'created_at' => $createdAt,
    ]);

    return $id;
}

it('requires sign-in for the inbox', function () {
    assertApiError($this->getJson('/api/v1/notifications'), 401, 'unauthenticated');
});

it('lists the reader\'s notifications newest first with the unread count', function () {
    $user = reader();
    $old = inboxItem($user, ['title' => 'Old'], readAt: now()->subDay()->toDateTimeString(), createdAt: now()->subDays(2)->toDateTimeString());
    $new = inboxItem($user, ['title' => 'New']);
    inboxItem(reader(), ['title' => 'Someone else']);

    $this->actingAs($user)->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $new)
        ->assertJsonPath('data.0.title', 'New')
        ->assertJsonPath('data.0.read_at', null)
        ->assertJsonPath('data.1.id', $old)
        ->assertJsonPath('meta.unread_count', 1)
        ->assertJsonStructure(['data' => [['id', 'type', 'title', 'body', 'url', 'read_at', 'created_at']], 'meta' => ['next_cursor']]);

    $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 1);
});

it('marks one or all notifications as read, only for their owner', function () {
    $user = reader();
    $first = inboxItem($user);
    inboxItem($user);
    $foreign = inboxItem(reader());

    $this->actingAs($user)->postJson("/api/v1/notifications/{$foreign}/read")->assertNotFound();

    $this->postJson("/api/v1/notifications/{$first}/read")->assertNoContent();
    expect($user->unreadNotifications()->count())->toBe(1);

    $this->postJson('/api/v1/notifications/read-all')->assertNoContent();
    expect($user->unreadNotifications()->count())->toBe(0)
        ->and(User::query()->whereKeyNot($user->id)->first()->unreadNotifications()->count())->toBe(1);
});

it('stores campaign notifications in the reader\'s language', function () {
    $greek = reader(['locale' => 'el']);
    $campaign = PushCampaign::query()->create(['title' => ['en' => 'Hello', 'el' => 'Γεια'], 'body' => ['en' => 'Body', 'el' => 'Κείμενο'], 'url' => '/digests']);

    $greek->notify(new CampaignNotification($campaign));

    $this->actingAs($greek)->getJson('/api/v1/notifications')
        ->assertJsonPath('data.0.title', 'Γεια')
        ->assertJsonPath('data.0.body', 'Κείμενο')
        ->assertJsonPath('data.0.url', '/digests')
        ->assertJsonPath('data.0.type', 'campaign');
});
