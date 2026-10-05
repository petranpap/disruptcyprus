<?php

it('returns opt-in defaults', function () {
    $this->actingAs(reader())->getJson('/api/v1/me/notification-preferences')
        ->assertOk()
        ->assertExactJson(['data' => [
            'digest_news_daily' => false,
            'digest_news_monthly' => false,
            'digest_events_weekly' => false,
            'digest_events_monthly' => false,
            'event_reminders' => true,
            'delivery_time' => '08:00',
        ]]);
});

it('updates preferences partially', function () {
    $user = reader();

    $this->actingAs($user)->putJson('/api/v1/me/notification-preferences', [
        'digest_news_daily' => true,
        'delivery_time' => '07:30',
    ])->assertOk()
        ->assertJsonPath('data.digest_news_daily', true)
        ->assertJsonPath('data.digest_events_weekly', false)
        ->assertJsonPath('data.delivery_time', '07:30');
});

it('validates the delivery time', function () {
    $this->actingAs(reader())->putJson('/api/v1/me/notification-preferences', ['delivery_time' => '25:00'])
        ->assertUnprocessable()->assertJsonValidationErrors('delivery_time');
});
