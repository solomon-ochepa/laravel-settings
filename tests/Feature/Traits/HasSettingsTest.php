<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\MorphMany;
use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Tests\App\Models\User;

it('settings() returns a MorphMany relation', function () {
    expect(User::factory()->make()->settings())->toBeInstanceOf(MorphMany::class);
});

it('reads and writes settings through the settable morph columns', function () {
    $user = User::factory()->create();

    $user->settings()->create([
        'name' => 'via_relation',
        'value' => 'relation_value',
        'group' => 'default',
    ]);

    expect($user->settings()->count())->toBe(1);
    $this->assertDatabaseHas('settings', [
        'name' => 'via_relation',
        'settable_type' => User::class,
        'settable_id' => $user->id,
    ]);

    // The relation and the repository's for() scoping read the same rows.
    expect(Settings::for($user)->get('via_relation'))->toEqual('relation_value');
});
