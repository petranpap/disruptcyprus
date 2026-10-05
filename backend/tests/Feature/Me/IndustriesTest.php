<?php

use App\Models\Industry;

it('returns and replaces followed industries with notification flags', function () {
    $user = reader();
    [$fintech, $ai, $maritime] = Industry::factory()->count(3)->create();

    $this->actingAs($user)->getJson('/api/v1/me/industries')
        ->assertOk()->assertExactJson(['data' => ['industry_ids' => [], 'notify_ids' => []]]);

    $this->actingAs($user)->putJson('/api/v1/me/industries', [
        'industry_ids' => [$fintech->id, $ai->id, $maritime->id],
        'notify_ids' => [$ai->id],
    ])->assertOk()->assertJsonPath('data.notify_ids', [$ai->id]);

    expect($user->industries()->pluck('industries.id')->sort()->values()->all())
        ->toBe(collect([$fintech->id, $ai->id, $maritime->id])->sort()->values()->all());

    $this->actingAs($user)->putJson('/api/v1/me/industries', ['industry_ids' => []])
        ->assertOk()->assertJsonPath('data.industry_ids', []);
});

it('only allows notifications for followed industries', function () {
    [$followed, $other] = Industry::factory()->count(2)->create();

    $this->actingAs(reader())->putJson('/api/v1/me/industries', [
        'industry_ids' => [$followed->id],
        'notify_ids' => [$other->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('notify_ids.0');
});

it('rejects inactive or unknown industries', function () {
    $inactive = Industry::factory()->inactive()->create();

    $this->actingAs(reader())->putJson('/api/v1/me/industries', [
        'industry_ids' => [$inactive->id, 999999],
    ])->assertUnprocessable()->assertJsonValidationErrors(['industry_ids.0', 'industry_ids.1']);
});
