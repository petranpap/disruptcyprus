<?php

use App\Jobs\ExportUserData;
use App\Models\Industry;
use App\Notifications\DataExportReady;
use App\Services\Account\UserDataExporter;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => RateLimiter::clear('exports'));

it('queues the export and limits it to once per hour', function () {
    Queue::fake();
    $user = reader();

    $this->actingAs($user)->postJson('/api/v1/me/export')->assertAccepted();
    Queue::assertPushed(ExportUserData::class, fn (ExportUserData $job) => $job->user->is($user));

    assertApiError($this->actingAs($user)->postJson('/api/v1/me/export'), 429, 'too_many_requests');
});

it('builds the export file and sends a signed download link', function () {
    Storage::fake('local');
    Notification::fake();
    $user = reader(['email' => 'reader@example.com']);
    $user->industries()->attach(Industry::factory()->create(['slug' => 'fintech']), ['notify' => true]);

    (new ExportUserData($user))->handle(app(UserDataExporter::class));

    $sent = null;
    Notification::assertSentTo($user, DataExportReady::class, function (DataExportReady $notification) use (&$sent) {
        $sent = $notification;

        return true;
    });

    $url = $sent->downloadUrl($user);
    $response = $this->get($url)->assertOk();

    $payload = json_decode($response->streamedContent(), true);
    expect($payload['account']['email'])->toBe('reader@example.com')
        ->and($payload['followed_industries'][0])->toBe(['slug' => 'fintech', 'notify' => true]);
});

it('rejects unsigned download links', function () {
    $user = reader();

    $this->getJson("/api/v1/me/export/{$user->id}/".str_repeat('a', 40).'.json')->assertForbidden();
});
