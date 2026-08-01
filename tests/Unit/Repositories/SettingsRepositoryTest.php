<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\Tests\App\Models\User;

describe('cache_key()', function () {
    it('is deterministic and distinct per group/settable/key combination', function () {
        $repo = (new SettingsRepository)->group('admin');

        expect(cacheKeyOf($repo, 'foo'))->toEqual(cacheKeyOf($repo, 'foo'));
        expect(cacheKeyOf($repo, 'foo'))->not->toEqual(cacheKeyOf($repo, 'bar'));
        expect(cacheKeyOf($repo, 'foo'))->not->toEqual(cacheKeyOf((new SettingsRepository)->group('user'), 'foo'));
        expect(cacheKeyOf($repo, 'foo'))->toStartWith('settings.');
    });

    it('does not collide between a real (type, id) scope and a crafted colliding string', function () {
        // make(), not create(): this is pure key-generation logic and must
        // not touch the database.
        $victim = User::factory()->make(['id' => (string) Str::uuid()]);

        $victimKey = cacheKeyOf((new SettingsRepository)->for($victim));
        $craftedKey = cacheKeyOf((new SettingsRepository)->for(User::class.'_'.$victim->id));

        expect($victimKey)->not->toEqual($craftedKey);
    });
});

describe('scoping state', function () {
    it('group() re-scopes to the last value when called twice, rather than stacking', function () {
        $repo = new SettingsRepository;

        $repo->group('admin')->group('user');

        expect($repo->group)->toEqual(['user']);
    });

    it('for() re-scopes to the last entity when called twice, rather than stacking', function () {
        $user1 = User::factory()->make();
        $user2 = User::factory()->make();
        $repo = new SettingsRepository;

        $repo->for($user1)->for($user2);

        expect($repo->settable)->toBe($user2);
    });

    it('for() clears the settable scope back to null on a falsy value or no argument', function (bool $withArgument) {
        $repo = (new SettingsRepository)->for(User::factory()->make());

        $withArgument ? $repo->for(null) : $repo->for();

        expect($repo->settable)->toBeNull();
    })->with([
        'for(null)' => [true],
        'for() with no argument' => [false],
    ]);
});

describe('misuse', function () {
    it('for() throws a TypeError for an invalid type', function () {
        expect(fn () => (new SettingsRepository)->for(['not', 'a', 'valid', 'settable']))
            ->toThrow(TypeError::class);
    });

    it('group() throws a TypeError for a non-string, non-array type', function () {
        expect(fn () => (new SettingsRepository)->group(123))
            ->toThrow(TypeError::class);
    });

    it('set() throws a TypeError for a list array (non-string keys)', function () {
        // Setting names must be strings; a plain list has no meaningful name
        // for each value, so this fails loudly instead of silently coercing
        // integer keys into stringified setting names. The TypeError is
        // raised on the recursive set() call before any storage is touched.
        expect(fn () => (new SettingsRepository)->set(['first', 'second']))
            ->toThrow(TypeError::class);
    });
});
