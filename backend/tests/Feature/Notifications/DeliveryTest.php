<?php

use App\Events\DigestPublished;
use App\Jobs\SendPushCampaign;
use App\Listeners\CountCampaignPushFailures;
use App\Listeners\NotifyFeaturedArticleFollowers;
use App\Listeners\QueueDigestNotifications;
use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Digest;
use App\Models\Industry;
use App\Models\PushCampaign;
use App\Models\User;
use App\Notifications\CampaignNotification;
use App\Notifications\DigestPublishedNotification;
use App\Notifications\EventReminderNotification;
use App\Notifications\FeaturedArticleNotification;
use Carbon\CarbonImmutable;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\Events\NotificationFailed;
use NotificationChannels\WebPush\WebPushMessage;

function subscriber(string $flag, array $attributes = [], string $deliveryTime = '08:00'): User
{
    $user = reader($attributes);
    $user->preferences()->update([$flag => true, 'delivery_time' => $deliveryTime]);

    return $user->load('notificationPreference');
}

/**
 * Like the admin panel: the article and its industries are saved in one transaction.
 */
function publishInTransaction(array $attributes, Industry $industry): Article
{
    return DB::transaction(fn () => newArticle($attributes, [$industry]));
}

describe('digests', function () {
    it('notifies only subscribers of that digest type, once', function () {
        Notification::fake();
        $daily = subscriber('digest_news_daily');
        subscriber('digest_events_weekly');
        reader();
        $digest = Digest::factory()->create();

        (new QueueDigestNotifications)->handle(new DigestPublished($digest));
        (new QueueDigestNotifications)->handle(new DigestPublished($digest));

        Notification::assertSentTimes(DigestPublishedNotification::class, 1);
        Notification::assertSentTo($daily, DigestPublishedNotification::class);
    });

    it('waits for the reader\'s delivery time in their own timezone', function () {
        $listener = new QueueDigestNotifications;
        $now = CarbonImmutable::parse('2026-10-07 04:00', 'UTC'); // 07:00 Nicosia, 05:00 London

        $nicosia = subscriber('digest_news_daily', ['timezone' => 'Asia/Nicosia'], '08:00');
        $london = subscriber('digest_news_daily', ['timezone' => 'Europe/London'], '08:00');

        expect($listener->deliveryAt($nicosia, $now)?->toIso8601String())->toBe('2026-10-07T05:00:00+00:00')
            ->and($listener->deliveryAt($london, $now)?->toIso8601String())->toBe('2026-10-07T07:00:00+00:00');
    });

    it('sends a late digest now during the day and holds it overnight', function () {
        $listener = new QueueDigestNotifications;
        $user = subscriber('digest_events_weekly', ['timezone' => 'Asia/Nicosia'], '08:00');

        expect($listener->deliveryAt($user, CarbonImmutable::parse('2026-10-04 18:00', 'Asia/Nicosia')))->toBeNull()
            ->and($listener->deliveryAt($user, CarbonImmutable::parse('2026-10-04 23:30', 'Asia/Nicosia'))?->toIso8601String())
            ->toBe('2026-10-05T05:00:00+00:00');
    });

    it('localizes the message and leads with a followed industry', function () {
        $fintech = Industry::factory()->create();
        $other = newArticle(['title' => ['en' => 'General story', 'el' => 'Γενικό θέμα']]);
        $followed = newArticle(['title' => ['en' => 'Fintech story', 'el' => 'Θέμα fintech']], [$fintech]);
        $digest = Digest::factory()->create();
        foreach ([$other, $followed] as $position => $article) {
            $digest->items()->create(['itemable_type' => 'article', 'itemable_id' => $article->id, 'position' => $position + 1]);
        }
        $greek = reader(['locale' => 'el']);
        $greek->industries()->attach($fintech);

        $greek->notify(new DigestPublishedNotification($digest));

        expect($greek->notifications()->sole()->data)->toMatchArray([
            'type' => 'digest_published',
            'title' => 'Ημερήσιες Ειδήσεις: νέο τεύχος',
            'body' => 'Κορυφαίο θέμα: Θέμα fintech',
            'url' => '/digests/'.$digest->slug,
        ]);
    });

    it('skips a delayed digest that was unpublished meanwhile', function () {
        $digest = Digest::factory()->create(['status' => 'draft', 'published_at' => null]);
        $user = reader();

        $user->notify(new DigestPublishedNotification($digest));

        expect($user->notifications()->count())->toBe(0);
    });
});

describe('featured articles', function () {
    it('alerts followers with alerts on when a featured article is published', function () {
        Notification::fake();
        $industry = Industry::factory()->create();
        $alerts = reader();
        $alerts->industries()->attach($industry, ['notify' => true]);
        $quiet = reader();
        $quiet->industries()->attach($industry, ['notify' => false]);

        publishInTransaction(['is_featured' => false], $industry);
        $featured = publishInTransaction(['is_featured' => true], $industry);

        Notification::assertSentTo($alerts, FeaturedArticleNotification::class, fn ($notification) => $notification->article->is($featured));
        Notification::assertSentTimes(FeaturedArticleNotification::class, 1);
        Notification::assertNotSentTo($quiet, FeaturedArticleNotification::class);
    });

    it('sends at most three a day to the same reader', function () {
        Notification::fake();
        $industry = Industry::factory()->create();
        $user = reader();
        $user->industries()->attach($industry, ['notify' => true]);

        foreach (range(1, 4) as $ignored) {
            publishInTransaction(['is_featured' => true], $industry);
        }

        Notification::assertSentToTimes($user, FeaturedArticleNotification::class, NotifyFeaturedArticleFollowers::DAILY_LIMIT);

        $this->travel(25)->hours();
        publishInTransaction(['is_featured' => true], $industry);
        Notification::assertSentToTimes($user, FeaturedArticleNotification::class, 4);
    });

    it('does not notify for scheduled articles until they go live', function () {
        Notification::fake();
        $industry = Industry::factory()->create();
        $user = reader();
        $user->industries()->attach($industry, ['notify' => true]);

        $article = newArticle(['is_featured' => true, 'status' => 'scheduled', 'published_at' => now()->addHour()], [$industry]);
        Notification::assertNothingSent();

        $this->travel(2)->hours();
        $this->artisan('content:publish-scheduled')->assertSuccessful();

        Notification::assertSentTo($user, FeaturedArticleNotification::class, fn ($notification) => $notification->article->is($article));
    });
});

describe('event reminders', function () {
    it('reminds readers who saved an event starting within a day, once', function () {
        Notification::fake();
        $soon = newEvent(['starts_at' => now()->addHours(20), 'ends_at' => now()->addHours(22)]);
        $later = newEvent(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour()]);
        $saver = reader();
        $optedOut = reader();
        $optedOut->preferences()->update(['event_reminders' => false]);
        foreach ([$saver, $optedOut] as $user) {
            Bookmark::query()->create(['user_id' => $user->id, 'bookmarkable_type' => 'event', 'bookmarkable_id' => $soon->id]);
            Bookmark::query()->create(['user_id' => $user->id, 'bookmarkable_type' => 'event', 'bookmarkable_id' => $later->id]);
        }

        $this->artisan('reminders:events')->assertSuccessful();
        $this->artisan('reminders:events')->assertSuccessful();

        Notification::assertSentTimes(EventReminderNotification::class, 1);
        Notification::assertSentTo($saver, EventReminderNotification::class, fn ($notification) => $notification->event->is($soon));
    });

    it('formats the reminder with the event time in Cyprus and its city', function () {
        // Travel first: the event must already be published at the (earlier) test time.
        $this->travelTo(CarbonImmutable::parse('2026-10-07 20:00', 'Asia/Nicosia'));
        $event = newEvent([
            'title' => ['en' => 'Pitch Night', 'el' => 'Βραδιά Pitch'],
            'starts_at' => CarbonImmutable::parse('2026-10-08 18:00', 'Asia/Nicosia'),
            'ends_at' => CarbonImmutable::parse('2026-10-08 21:00', 'Asia/Nicosia'),
            'city' => 'Larnaca', 'is_online' => false,
        ]);
        $user = reader(['locale' => 'en']);

        $user->notify(new EventReminderNotification($event));

        expect($user->notifications()->sole()->data)->toMatchArray([
            'type' => 'event_reminder',
            'title' => 'Reminder: Pitch Night',
            'body' => 'Thursday 6:00 PM · Larnaca',
            'url' => '/events/'.$event->slug,
        ]);
    });
});

describe('campaigns', function () {
    it('delivers to the whole audience and counts recipients', function () {
        $industry = Industry::factory()->create();
        $follower = reader();
        $follower->industries()->attach($industry);
        $outsider = reader();
        $campaign = PushCampaign::query()->create(['title' => ['en' => 'Hi', 'el' => 'Γεια'], 'body' => ['en' => 'B', 'el' => 'Κ'], 'audience' => 'industries', 'industry_ids' => [$industry->id]]);

        (new SendPushCampaign($campaign))->handle();

        expect($campaign->refresh()->recipients_count)->toBe(1)
            ->and($follower->notifications()->count())->toBe(1)
            ->and($outsider->notifications()->count())->toBe(0);
    });

    it('counts failed pushes on the campaign', function () {
        $campaign = PushCampaign::query()->create(['title' => ['en' => 'Hi'], 'body' => ['en' => 'B']]);
        $user = reader();
        $subscription = $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/x', 'key', 'token', 'aes128gcm');
        $message = (new CampaignNotification($campaign))->toWebPush($user, new CampaignNotification($campaign));
        $report = new MessageSentReport(new PsrRequest('POST', $subscription->endpoint), null, false, 'Gone');

        (new CountCampaignPushFailures)->handle(new NotificationFailed($report, $subscription, $message));
        (new CountCampaignPushFailures)->handle(new NotificationFailed($report, $subscription, (new WebPushMessage)->title('Other')));

        expect($campaign->refresh()->failures_count)->toBe(1);
    });
});
