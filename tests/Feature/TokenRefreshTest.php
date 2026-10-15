<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('refreshes an expired token that is still inside the refresh window', function () {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    Auth::guard('api')->forgetUser();

    $this->travel(config('jwt.ttl') + 5)->minutes();

    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();

    $response = $this->withToken($token)->postJson('/api/refresh')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    expect($response->json('access_token'))->not->toBe($token);
});

it('returns 401 when refreshing without a token', function () {
    $this->postJson('/api/refresh')->assertUnauthorized();
});
