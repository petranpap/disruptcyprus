<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('uploads and removes an avatar', function () {
    $user = reader();

    $this->actingAs($user)->post('/api/v1/me/avatar', [
        'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.avatar_url', fn (?string $url) => $url !== null && str_contains($url, 'thumb'));

    $this->actingAs($user)->deleteJson('/api/v1/me/avatar')
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);
});

it('rejects non-images and tiny images', function () {
    $this->actingAs(reader())->post('/api/v1/me/avatar', [
        'avatar' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('avatar');

    $this->actingAs(reader())->post('/api/v1/me/avatar', [
        'avatar' => UploadedFile::fake()->image('tiny.png', 10, 10),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('avatar');
});
