<?php

use App\Models\Place;
use App\Models\User;

test('a followed place exposes its pivot flags, which default to false', function () {
    $user = User::factory()->create();
    $place = Place::factory()->create();

    $user->places()->attach($place);

    expect($user->places()->first()->pivot)
        ->is_favorite->toBeFalse()
        ->send_forecast->toBeFalse();
});

test('a place can be marked as favorite with forecast sending enabled', function () {
    $user = User::factory()->create();
    $place = Place::factory()->create();

    $user->places()->attach($place, ['is_favorite' => true, 'send_forecast' => true]);

    $this->assertDatabaseHas('place_user', [
        'user_id' => $user->id,
        'place_id' => $place->id,
        'is_favorite' => true,
        'send_forecast' => true,
    ]);
});

test('deleting a user removes the places they followed from the pivot', function () {
    $user = User::factory()->create();
    $place = Place::factory()->create();
    $user->places()->attach($place);

    $user->delete();

    $this->assertDatabaseEmpty('place_user');
    $this->assertModelExists($place);
});

test('a new user uses the metric unit by default', function () {
    $user = User::factory()->create();

    expect($user->fresh()->unit)->toBe('metric');
});
