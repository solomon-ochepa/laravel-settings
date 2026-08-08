<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Models\Setting;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\Tests\App\Models\User;

describe('storage', function () {
    it('writes and reads a setting through either the facade or the helper', function (Closure $set, Closure $get) {
        $set('name', 'Settings');

        expect($get('name'))->toEqual('Settings');
        $this->assertDatabaseHas('settings', ['name' => 'name']);
    })->with([
        'facade' => [fn ($k, $v) => Settings::set($k, $v), fn ($k) => Settings::get($k)],
        'helper' => [fn ($k, $v) => settings([$k => $v]), fn ($k) => settings($k)],
    ]);

    it('stores the value JSON-encoded, in the given group', function (?string $group, string $expectedGroup) {
        $repo = $group ? settings()->group($group) : settings();
        $repo->set('name', 'Settings');

        $this->assertDatabaseHas('settings', [
            'name' => 'name',
            'value' => '"Settings"',
            'group' => $expectedGroup,
        ]);
    })->with([
        'default group' => [null, 'default'],
        'custom group' => ['user', 'user'],
    ]);

    it('upserts instead of duplicating a row for the same key', function (string $secondValue) {
        Settings::set('name', 'Settings');
        Settings::set('name', $secondValue);

        expect(Settings::all())->toHaveCount(1);
        expect(Settings::get('name'))->toEqual($secondValue);
    })->with([
        'set again with the same value' => ['Settings'],
        'set again with a different value' => ['Laravel'],
    ]);

    it('creates a separate row for a different key without touching existing ones', function () {
        Settings::set('name', 'Settings');
        Settings::set('email', 'Settings');

        expect(Settings::all())->toHaveCount(2);
    });

    it('returns a default when the key is missing', function () {
        $this->assertDatabaseMissing('settings', ['name' => 'name']);

        expect(Settings::get('name', 'Laravel'))->toEqual('Laravel');
    });

    it('stores multiple settings from an array and returns the first value', function () {
        $result = Settings::set([
            'name' => 'Settings',
            'email' => 'info@example.com',
            'tag' => 'SaaS',
        ]);

        expect($result)->toEqual('Settings');
        expect(Settings::all())->toHaveCount(3);
        expect(Settings::get('name'))->toEqual('Settings');
        expect(Settings::get('email'))->toEqual('info@example.com');
        expect(Settings::get('tag'))->toEqual('SaaS');
    });

    it('returns the value for a single key', function () {
        expect(Settings::set('foo', 'bar'))->toEqual('bar');
    });

    it('is a no-op and does not throw for an empty array', function () {
        $result = Settings::set([]);

        expect($result)->toBeNull();
        expect(Settings::all())->toHaveCount(0);
    });

    it('has() and missing() reflect whether a key exists', function () {
        Settings::set('theme', 'dark');

        expect(Settings::has('theme'))->toBeTrue();
        expect(Settings::has('nonexistent'))->toBeFalse();
        expect(Settings::missing('theme'))->toBeFalse();
        expect(Settings::missing('nonexistent'))->toBeTrue();
    });

    it('add() is an alias for set()', function () {
        Settings::add('site_name', 'MySite');
        expect(Settings::get('site_name'))->toEqual('MySite');

        Settings::add(['foo' => 'bar', 'baz' => 'qux']);
        expect(Settings::get('foo'))->toEqual('bar');
        expect(Settings::get('baz'))->toEqual('qux');
    });
});

describe('groups', function () {
    it('keeps the default group settings separate from a named group', function () {
        settings()->set('name', 'Settings');
        settings()->set('slug', 'settings');

        expect(settings()->all())->toHaveCount(2);
        expect(settings()->group('user')->all())->toHaveCount(0);
    });

    it('isolates the same setting name across different groups', function () {
        Settings::group('default')->set('name', 'Laravel');
        Settings::group('user')->set('name', 'Users');
        Settings::group('product')->set('name', 'Products');

        // Each read below re-scopes explicitly — the Settings facade caches
        // one instance per request, so scope from a prior call would
        // otherwise leak into the next (see the "chaining" describe block).
        expect(Settings::group('default')->get('name'))->toEqual('Laravel');
        expect(Settings::group('user')->get('name'))->toEqual('Users');
        expect(Settings::group('product')->get('name'))->toEqual('Products');
        expect(Settings::group('user')->has('name'))->toBeTrue();
        expect(Settings::group('nonexistent')->has('name'))->toBeFalse();
    });

    describe('multi-group all()', function () {
        it('returns a collection nested by group name', function () {
            Settings::set('name', 'Laravel');
            Settings::group('user')->set('name', 'Users');
            Settings::group('product')->set('name', 'Products');

            expect(Settings::group(['user', 'product'])->all())->toHaveCount(2);
        });

        it('does not mutate the repository group state', function () {
            $repo = new SettingsRepository;
            $repo->group(['admin', 'user']);

            $repo->all();

            expect($repo->group)->toEqual(['admin', 'user']);
        });

        it('reflects a write to one of its groups without a stale combined cache', function () {
            Settings::group(['admin', 'user'])->all();

            Settings::group('admin')->set('theme', 'dark');

            $combined = Settings::group(['admin', 'user'])->all();
            expect($combined->get('admin')->get('theme'))->toEqual('dark');
        });

        it('treats a single-element group array like a plain group, not a multi-group', function () {
            Settings::group('admin')->set('theme', 'dark');

            $result = Settings::group(['admin'])->all();

            expect($result->get('theme'))->toEqual('dark');
        });

        it('is not searched by has()/get(), which look among group names instead (known limitation)', function () {
            Settings::group('admin')->set('theme', 'dark');
            Settings::group('user')->set('theme', 'light');

            expect(Settings::group(['admin', 'user'])->has('theme'))->toBeFalse();
            expect(Settings::group(['admin', 'user'])->get('theme', 'fallback'))->toEqual('fallback');
        });
    });
});

describe('settable scoping', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('scopes settings to a specific entity via for()', function () {
        settings()->for($this->user)->set('theme', 'dark');

        expect(settings()->for($this->user)->get('theme'))->toBe('dark');
    });

    it('does not leak settable-scoped settings into or read from the global scope', function () {
        (new SettingsRepository)->set('theme', 'light');
        (new SettingsRepository)->for($this->user)->set('theme', 'dark');

        expect((new SettingsRepository)->get('theme'))->toEqual('light');
        expect((new SettingsRepository)->for($this->user)->get('theme'))->toEqual('dark');
    });

    it('clears the scope back to global when for() receives a falsy value or no argument', function (bool $withArgument) {
        Settings::for($this->user)->set('theme', 'dark');

        $withArgument ? Settings::for(null) : Settings::for();
        Settings::set('theme', 'light');

        expect(Settings::get('theme'))->toEqual('light');
        expect(Settings::for($this->user)->get('theme'))->toEqual('dark');
    })->with([
        'for(null)' => [true],
        'for() with no argument' => [false],
    ]);

    it('scopes by a class-string alone, without a specific entity instance', function () {
        (new SettingsRepository)->for(User::class)->set('quota', 100);

        expect((new SettingsRepository)->for(User::class)->get('quota'))->toEqual(100);
        expect((new SettingsRepository)->get('quota'))->toBeNull();
        expect((new SettingsRepository)->for($this->user)->get('quota'))->toBeNull();
    });

    it('falls back to the global scope for a guest with no authenticated user', function () {
        expect(Auth::user())->toBeNull();
        expect(fn () => Settings::user())->not->toThrow(Throwable::class);

        Settings::set('site_name', 'Guest visible');
        expect(Settings::user()->get('site_name'))->toEqual('Guest visible');
    });

    it('scopes to the authenticated user via user()', function () {
        $this->be($this->user);

        settings()->user()->set('theme', 'dark');

        expect(settings()->user()->get('theme'))->toBe('dark');
        expect(settings()->my('theme'))->toBe('dark');
    });
});

describe('caching', function () {
    it('serves cached data on subsequent calls without querying storage', function () {
        Settings::set('name', 'Settings');
        Settings::all(); // warm the cache

        // Mutate storage directly, bypassing the repository, to prove the
        // next read comes from the cache rather than the database.
        Setting::query()->where('name', 'name')->update(['value' => '"Mutated"']);

        expect(Settings::all()->get('name'))->toEqual('Settings');
    });

    it('invalidates the cache for its own group when a setting is written', function () {
        Settings::set('name', 'Settings');
        Settings::all();

        Settings::set('name', 'Laravel');

        expect(Settings::all()->get('name'))->toEqual('Laravel');
    });

    it('flush() forgets only the current scope\'s cache entry', function () {
        $repo = new SettingsRepository;

        Cache::shouldReceive('forget')->once()->with(cacheKeyOf($repo));

        $repo->flush();
    });

    it('setting $flush wipes the entire cache store, not just settings (sharp edge)', function () {
        Cache::put('unrelated_cache_key', 'should be wiped', 3600);

        $repo = new SettingsRepository;
        $repo->flush = true;
        $repo->all();

        expect(Cache::has('unrelated_cache_key'))->toBeFalse();
    });

    it('caches the settings using the configured TTL', function () {
        $repo = new SettingsRepository;

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once()->with(Mockery::any(), Mockery::any(), $repo->cache_ttl);

        $repo->all();
    });

    it('cannot be poisoned by a crafted colliding settable string (see Unit test for the key-collision proof)', function () {
        $victim = User::factory()->create();

        (new SettingsRepository)->for($victim)->set('secret', 'victim-private-data');

        // Attacker crafts a settable scope designed to collide with the
        // victim's (type, id) cache key, and primes the cache with it
        // first.
        (new SettingsRepository)->for(User::class.'_'.$victim->id)->all();

        // The victim must still see their own real data, not an
        // empty/poisoned collection contributed by the attacker's
        // crafted scope.
        expect((new SettingsRepository)->for($victim)->get('secret'))->toEqual('victim-private-data');
    });
});

describe('when the settings table is missing', function () {
    beforeEach(function () {
        Schema::shouldReceive('hasTable', 'dropIfExists')->andReturn(false);
    });

    it('all() returns an empty collection', function () {
        expect(Settings::all())->toBeInstanceOf(Collection::class);
        expect(Settings::all())->toHaveCount(0);
    });

    it('get(), has(), and missing() degrade gracefully', function () {
        expect(Settings::get('name', 'fallback'))->toEqual('fallback');
        expect(Settings::has('name'))->toBeFalse();
        expect(Settings::missing('name'))->toBeTrue();
    });

    it('flashes a debug session message only when app.debug is enabled', function (bool $debug, bool $shouldFlash) {
        config(['app.debug' => $debug]);

        Settings::all();

        expect(session()->has('#settings table not found.'))->toBe($shouldFlash);
    })->with([
        'debug enabled' => [true, true],
        'debug disabled' => [false, false],
    ]);
});

describe('trash(), restore(), and delete()', function () {
    it('trash() soft-deletes a setting', function () {
        Settings::set('api_key', '12345');
        $this->assertDatabaseHas('settings', ['name' => 'api_key']);

        Settings::trash('api_key');

        $this->assertSoftDeleted('settings', ['name' => 'api_key']);
    });

    it('trash() invalidates the cache so the setting reads as gone', function () {
        Settings::set('api_key', '12345');
        Settings::all(); // warm the cache

        $trashed = Settings::trash('api_key');

        expect($trashed)->toBe(1);
        expect(Settings::has('api_key'))->toBeFalse();
        expect(Settings::get('api_key'))->toBeNull();
    });

    it('trash() on a nonexistent key is a no-op', function () {
        expect(Settings::trash('does_not_exist'))->toBe(0);
    });

    it('restore() brings back a trashed setting', function () {
        Settings::set('restore_me', 'yes');
        Settings::trash('restore_me');
        $this->assertSoftDeleted('settings', ['name' => 'restore_me']);

        Settings::restore('restore_me');

        $this->assertDatabaseHas('settings', ['name' => 'restore_me', 'deleted_at' => null]);
    });

    it('restore() invalidates the cache so the setting reads as present again', function () {
        Settings::set('restore_me', 'yes');
        Settings::trash('restore_me');
        Settings::all(); // warm the "gone" state into cache

        $restored = Settings::restore('restore_me');

        expect($restored)->toBe(1);
        expect(Settings::has('restore_me'))->toBeTrue();
        expect(Settings::get('restore_me'))->toEqual('yes');
    });

    it('restore() on a key that was never trashed is a no-op', function () {
        Settings::set('never_trashed', 'value');

        expect(Settings::restore('never_trashed'))->toBe(0);
    });

    it('delete() permanently removes a trashed setting', function () {
        Settings::set('permanent', 'gone');
        Settings::trash('permanent');

        Settings::delete('permanent');

        $this->assertDatabaseMissing('settings', ['name' => 'permanent']);
    });

    it('delete() only affects trashed rows, not active ones', function () {
        Settings::set('active', 'still here');

        $deleted = Settings::delete('active');

        expect($deleted)->toBe(0);
        expect(Settings::get('active'))->toEqual('still here');
    });
});

describe('remember()', function () {
    it('returns the existing value without overwriting it, when truthy', function () {
        Settings::set('existing_key', 'existing_value');

        $result = Settings::remember('existing_key', 'default_value');

        expect($result)->toEqual('existing_value');
        expect(Settings::all())->toHaveCount(1);
    });

    it('sets and returns the default when the key does not exist', function () {
        $this->assertDatabaseMissing('settings', ['name' => 'new_key']);

        $result = Settings::remember('new_key', 'default_value');

        expect($result)->toEqual('default_value');
        $this->assertDatabaseHas('settings', ['name' => 'new_key', 'value' => '"default_value"']);
    });

    it('preserves an existing falsy value instead of overwriting it', function (mixed $falsyValue) {
        Settings::set('existing_falsy', $falsyValue);

        $result = Settings::remember('existing_falsy', 'some_default');

        expect($result)->toEqual($falsyValue);
        expect(Settings::get('existing_falsy'))->toEqual($falsyValue);
    })->with('falsy setting values');

    it('treats values that are falsy in other contexts as present and truthy here', function () {
        Settings::set('string_zero', '0');
        expect(Settings::remember('string_zero', 'default_value'))->toEqual('0');

        Settings::set('array_with_false', [false]);
        expect(Settings::remember('array_with_false', 'default_value'))->toEqual([false]);
    });

    it('supports scalar/array default types', function (mixed $default) {
        $result = Settings::remember('key', $default);

        expect($result)->toEqual($default);
        expect(Settings::get('key'))->toEqual($default);
    })->with([
        'array' => [['default', 'array']],
        'numeric' => [42],
        'boolean true' => [true],
    ]);

    it('json-round-trips an object default into an array', function () {
        $object = (object) ['key' => 'value'];

        $result = Settings::remember('object_key', $object);

        expect($result)->toEqual($object);
        // The json cast decodes objects back into associative arrays.
        expect(Settings::get('object_key'))->toEqual((array) $object);
    });

    it('supports null as the default', function () {
        $result = Settings::remember('null_default_key', null);

        expect($result)->toBeNull();
        $this->assertDatabaseHas('settings', ['name' => 'null_default_key', 'value' => null]);
    });

    it('scopes independently per group', function () {
        Settings::group('admin')->set('theme', 'admin_theme');

        expect(Settings::group('admin')->remember('theme', 'default_theme'))->toEqual('admin_theme');
        expect(Settings::group('user')->remember('theme', 'user_default_theme'))->toEqual('user_default_theme');
        expect(Settings::group('admin')->get('theme'))->toEqual('admin_theme');
        expect(Settings::group('user')->get('theme'))->toEqual('user_default_theme');
    });

    it('scopes independently per settable entity', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Settings::for($user1)->set('preference', 'user1_pref');

        expect(Settings::for($user1)->remember('preference', 'default_pref'))->toEqual('user1_pref');
        expect(Settings::for($user2)->remember('preference', 'user2_default'))->toEqual('user2_default');
        expect(Settings::for($user1)->get('preference'))->toEqual('user1_pref');
        expect(Settings::for($user2)->get('preference'))->toEqual('user2_default');
    });

    it('works via the helper function', function () {
        $result = settings()->remember('helper_key', 'helper_default');

        expect($result)->toEqual('helper_default');
        expect(settings()->get('helper_key'))->toEqual('helper_default');
    });

    it('keeps returning the preserved falsy value across repeated calls with different defaults', function () {
        Settings::set('complex_key', false);

        expect(Settings::remember('complex_key', 'new_value'))->toBeFalse();
        expect(Settings::remember('complex_key', 'another_default'))->toBeFalse();
    });
});

describe('chaining', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('scopes identically regardless of group()/for() call order', function () {
        (new SettingsRepository)->group('admin')->for($this->user)->set('theme', 'dark');

        expect((new SettingsRepository)->for($this->user)->group('admin')->get('theme'))->toEqual('dark');
        expect((new SettingsRepository)->group('admin')->for($this->user)->get('theme'))->toEqual('dark');
    });

    it('a repeated group() call only affects the last-scoped group\'s data', function () {
        (new SettingsRepository)->group('admin')->set('theme', 'admin_theme');

        (new SettingsRepository)->group('admin')->group('user')->set('theme', 'user_theme');

        expect((new SettingsRepository)->group('user')->get('theme'))->toEqual('user_theme');
        expect((new SettingsRepository)->group('admin')->get('theme'))->toEqual('admin_theme');
    });

    it('re-scopes to the last entity when for() is called twice, rather than stacking', function () {
        $otherUser = User::factory()->create();

        (new SettingsRepository)->for($this->user)->set('theme', 'user1_theme');
        (new SettingsRepository)->for($this->user)->for($otherUser)->set('theme', 'user2_theme');

        expect((new SettingsRepository)->for($this->user)->get('theme'))->toEqual('user1_theme');
        expect((new SettingsRepository)->for($otherUser)->get('theme'))->toEqual('user2_theme');
    });

    it('combines user() and group() to scope to both the auth user and the group', function () {
        $this->actingAs($this->user);

        settings()->user()->group('preferences')->set('theme', 'dark');

        expect(settings()->user()->group('preferences')->get('theme'))->toEqual('dark');
        expect(settings()->group('preferences')->get('theme'))->toBeNull();
        expect(settings()->user()->get('theme'))->toBeNull();
    });

    it('add() respects the current group and settable scope', function () {
        (new SettingsRepository)->group('admin')->for($this->user)->add('theme', 'dark');

        expect((new SettingsRepository)->group('admin')->for($this->user)->get('theme'))->toEqual('dark');
        expect((new SettingsRepository)->get('theme'))->toBeNull();
    });

    it('remember() respects a chained group() and for() scope', function () {
        $result = (new SettingsRepository)->group('admin')->for($this->user)->remember('theme', 'default_theme');

        expect($result)->toEqual('default_theme');
        expect((new SettingsRepository)->group('admin')->for($this->user)->get('theme'))->toEqual('default_theme');
        expect((new SettingsRepository)->get('theme'))->toBeNull();
    });

    it('retains scope set by a prior Settings:: call within the same request (known behavior)', function () {
        // Facade static calls resolve and cache one underlying instance for
        // the life of the request, so scope set via for()/group() persists
        // across separate Settings:: calls unless explicitly cleared. Prefer
        // chaining in a single statement, or use settings()/`new
        // SettingsRepository` for calls that must stay isolated.
        Settings::for($this->user)->set('theme', 'dark');

        expect(Settings::get('theme'))->toEqual('dark');

        Settings::for(null); // must explicitly clear scope back to global
        expect(Settings::get('theme'))->toBeNull();
    });
});

describe('return types', function () {
    // One check per SettingsInterface method, matching its declared return
    // type (self, Collection, bool, or a concrete type for the mixed ones).

    it('group(), for(), and user() return self for chaining', function (Closure $call) {
        expect($call(new SettingsRepository))->toBeInstanceOf(SettingsRepository::class);
    })->with([
        'group()' => [fn ($repo) => $repo->group('admin')],
        'for()' => [fn ($repo) => $repo->for(User::factory()->make())],
        'user()' => [fn ($repo) => $repo->user()],
    ]);

    it('all() returns a Collection', function () {
        Settings::set('name', 'Settings');

        expect(Settings::all())->toBeInstanceOf(Collection::class);
    });

    it('all() returns a Collection of Collections when scoped to multiple groups', function () {
        $result = Settings::group(['admin', 'user'])->all();

        expect($result)->toBeInstanceOf(Collection::class);
        expect($result->get('admin'))->toBeInstanceOf(Collection::class);
    });

    it('my() returns the stored value, or null when missing', function () {
        $this->be(User::factory()->create());
        Settings::user()->set('theme', 'dark');

        expect(Settings::my('theme'))->toBeString();
        expect(Settings::my('missing_key'))->toBeNull();
    });

    it('remember() returns the stored or default value as given', function () {
        expect(Settings::remember('new_key', 'default'))->toBeString();
        expect(Settings::remember('numeric_key', 42))->toBeInt();
    });

    it('get() returns the stored value, or null when no default is given', function () {
        Settings::set('name', 'Settings');

        expect(Settings::get('name'))->toBeString();
        expect(Settings::get('missing_key'))->toBeNull();
    });

    it('set() and add() return the value they were given', function (Closure $call) {
        expect($call('name', 'Settings'))->toBeString();
        expect($call('count', 5))->toBeInt();
    })->with([
        'set()' => [fn (...$args) => Settings::set(...$args)],
        'add()' => [fn (...$args) => Settings::add(...$args)],
    ]);

    it('set() returns null for an empty array (no-op)', function () {
        expect(Settings::set([]))->toBeNull();
    });

    it('has() and missing() return a strict bool', function () {
        Settings::set('name', 'Settings');

        expect(Settings::has('name'))->toBeBool();
        expect(Settings::missing('name'))->toBeBool();
    });

    it('trash(), restore(), and delete() return the affected row count as an int', function () {
        Settings::set('name', 'Settings');

        expect(Settings::trash('name'))->toBeInt();
        expect(Settings::restore('name'))->toBeInt();
        expect(Settings::delete('name'))->toBeInt();
    });

    it('flush() returns a strict bool', function () {
        expect((new SettingsRepository)->flush())->toBeBool();
    });
});

describe('misuse', function () {
    // Pure type-misuse cases (for()/group()/set() throwing a TypeError) live
    // in the Unit test — they never touch storage. This describe block only
    // covers misuse whose consequence is a persistence-level oversight.

    it('group([]) silently persists nothing, despite set() reporting success (known oversight)', function () {
        // Passing an empty array to group() leaves $this->group as [], which
        // is falsy. query()'s ->when($this->group, ...) then skips applying
        // any group scope, but set()'s foreach ((array) $this->group as
        // $group) has nothing to iterate, so no row is ever written — while
        // set() still returns the given value as if it succeeded.
        $result = Settings::group([])->set('key', 'value');

        expect($result)->toEqual('value');
        expect(Setting::count())->toBe(0);
    });

    it('for() accepts an unrecognized/typo\'d class-string without validation (known oversight)', function () {
        // for() only checks truthiness, not that the class exists; a
        // typo'd class-string silently creates its own, permanently
        // separate scope instead of failing fast.
        Settings::for('App\\Models\\TypoedClassName')->set('theme', 'dark');

        expect(Settings::for('App\\Models\\TypoedClassName')->get('theme'))->toEqual('dark');
        $this->assertDatabaseHas('settings', ['settable_type' => 'App\\Models\\TypoedClassName']);
    });
});
