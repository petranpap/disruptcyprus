<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/**
 * Headers that make Sanctum treat the request as coming from the PWA (cookie/session auth).
 *
 * @return array<string, string>
 */
function spaHeaders(): array
{
    return ['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/'];
}

/**
 * Asserts the shared API error envelope {message, code, errors?}.
 */
function assertApiError(TestResponse $response, int $status, string $code): TestResponse
{
    return $response->assertStatus($status)->assertJsonStructure(['message', 'code'])->assertJsonPath('code', $code);
}

function reader(array $attributes = []): User
{
    return User::factory()->create($attributes);
}
