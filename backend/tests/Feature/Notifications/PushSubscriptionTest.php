<?php

use App\Models\PushCampaign;
use App\Notifications\CampaignNotification;
use NotificationChannels\WebPush\WebPushChannel;

function subscriptionPayload(string $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123'): array
{
    return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'BPublicKey', 'auth' => 'authSecret'], 'content_encoding' => 'aes128gcm'];
}

it('exposes the VAPID public key without sign-in', function () {
    config(['webpush.vapid.public_key' => 'BTestPublicKey']);

    $this->getJson('/api/v1/push/public-key')->assertOk()->assertJsonPath('data.public_key', 'BTestPublicKey');
});

it('subscribes and unsubscribes a device', function () {
    $user = reader(['locale' => 'el']);

    $this->actingAs($user)->postJson('/api/v1/push/subscriptions', subscriptionPayload(), ['User-Agent' => 'Firefox/140'])
        ->assertCreated()->assertJsonPath('data.subscribed', true);
    $this->postJson('/api/v1/push/subscriptions', subscriptionPayload())->assertOk();

    $subscription = $user->pushSubscriptions()->sole();
    expect($subscription->public_key)->toBe('BPublicKey')
        ->and($subscription->getAttribute('locale'))->toBe('el')
        ->and($subscription->getAttribute('last_seen_at'))->not->toBeNull();

    $this->deleteJson('/api/v1/push/subscriptions', ['endpoint' => $subscription->endpoint])->assertNoContent();
    expect($user->pushSubscriptions()->count())->toBe(0);
});

it('moves a shared browser to the account that subscribed it last', function () {
    $first = reader();
    $second = reader();

    $this->actingAs($first)->postJson('/api/v1/push/subscriptions', subscriptionPayload())->assertCreated();
    $this->actingAs($second)->postJson('/api/v1/push/subscriptions', subscriptionPayload())->assertCreated();

    expect($first->pushSubscriptions()->count())->toBe(0)
        ->and($second->pushSubscriptions()->count())->toBe(1);
});

it('accepts only real push services as endpoints', function (string $endpoint) {
    $this->actingAs(reader())->postJson('/api/v1/push/subscriptions', subscriptionPayload($endpoint))
        ->assertUnprocessable()->assertJsonValidationErrors('endpoint');
})->with([
    'plain http' => 'http://fcm.googleapis.com/fcm/send/x',
    'internal host' => 'https://localhost/push',
    'look-alike host' => 'https://fcm.googleapis.com.evil.test/x',
    'metadata ip' => 'https://169.254.169.254/latest',
]);

it('accepts the major browser push services', function (string $endpoint) {
    $this->actingAs(reader())->postJson('/api/v1/push/subscriptions', subscriptionPayload($endpoint))->assertCreated();
})->with([
    'chrome' => 'https://fcm.googleapis.com/fcm/send/x',
    'firefox' => 'https://updates.push.services.mozilla.com/wpush/v2/x',
    'safari' => 'https://web.push.apple.com/x',
    'edge' => 'https://wns2-par02p.notify.windows.com/w/?token=x',
]);

it('adds Web Push only for readers with a subscribed device', function () {
    $campaign = PushCampaign::query()->create(['title' => ['en' => 'Hi'], 'body' => ['en' => 'Body']]);
    $withDevice = reader();
    $withDevice->updatePushSubscription('https://fcm.googleapis.com/fcm/send/x', 'key', 'token', 'aes128gcm');
    $notification = new CampaignNotification($campaign);

    expect($notification->via($withDevice))->toBe(['database', WebPushChannel::class])
        ->and($notification->via(reader()))->toBe(['database']);

    $message = $notification->toWebPush($withDevice, $notification)->toArray();
    expect($message['title'])->toBe('Hi')
        ->and($message['data'])->toMatchArray(['url' => '/', 'type' => 'campaign', 'campaign_id' => $campaign->id])
        ->and($message['icon'])->toBe('/pwa-192x192.png');
});

it('removes devices and dispatch records when the account is deleted', function () {
    $user = reader();
    $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/x', 'key', 'token', 'aes128gcm');
    $user->claimDispatch('featured:1');

    $this->actingAs($user, 'web')->withHeaders(spaHeaders())->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

    $this->assertDatabaseCount('push_subscriptions', 0);
    $this->assertDatabaseCount('notification_dispatches', 0);
});
