<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\Tests\App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['settings.cache.enable' => false]);
});

test('add() is an alias for set()', function () {
    Settings::add('site_name', 'MySite');
    expect(Settings::get('site_name'))->toEqual('MySite');

    Settings::add(['foo' => 'bar', 'baz' => 'qux']);
    expect(Settings::get('foo'))->toEqual('bar');
    expect(Settings::get('baz'))->toEqual('qux');
});

test('has() returns true if setting exists, false otherwise', function () {
    Settings::set('theme', 'dark');
    expect(Settings::has('theme'))->toBeTrue();
    expect(Settings::has('nonexistent'))->toBeFalse();
});

test('missing() returns true if setting does not exist', function () {
    Settings::set('timezone', 'UTC');
    expect(Settings::missing('timezone'))->toBeFalse();
    expect(Settings::missing('notfound'))->toBeTrue();
});

test('trash() deletes a setting (soft delete)', function () {
    Settings::set('api_key', '12345');
    $this->assertDatabaseHas('settings', ['name' => 'api_key']);
    Settings::trash('api_key');
    $this->assertSoftDeleted('settings', ['name' => 'api_key']);
});

test('restore() brings back a trashed setting', function () {
    Settings::set('restore_me', 'yes');
    Settings::trash('restore_me');
    $this->assertSoftDeleted('settings', ['name' => 'restore_me']);
    Settings::restore('restore_me');
    $this->assertDatabaseHas('settings', ['name' => 'restore_me', 'deleted_at' => null]);
});

test('delete() permanently removes a trashed setting', function () {
    Settings::set('permanent', 'gone');
    Settings::trash('permanent');
    Settings::delete('permanent');
    $this->assertDatabaseMissing('settings', ['name' => 'permanent']);
});

test('flush() clears the cache', function () {
    Cache::shouldReceive('forget')->once()->with('settings.default_');

    config(['settings.cache.enable' => true]);

    (new SettingsRepository)->flush();
});

test('cache_key generates correct key for group and key', function () {
    $repo = new SettingsRepository;
    $repo->group('admin');
    $key = (new \ReflectionClass($repo))->getMethod('cache_key');
    $key->setAccessible(true);
    expect($key->invoke($repo, 'foo'))->toEqual('settings.admin.foo_');
});

test('my() returns user setting', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    settings()->user()->set('my_key', 'my_value');
    expect(settings()->user()->get('my_key'))->toEqual('my_value');
    expect(settings()->my('my_key'))->toEqual('my_value');
});

test('set() and get() work with multiple groups', function () {
    settings()->group(['admin', 'user'])->set('multi', 'value');

    expect(settings()->group('admin')->get('multi'))->toEqual('value');
    expect(settings()->group('user')->get('multi'))->toEqual('value');
});

test('set() returns value for single key', function () {
    $result = Settings::set('foo', 'bar');
    expect($result)->toEqual('bar');
});

test('set() returns first value for array', function () {
    $result = Settings::set(['a' => 1, 'b' => 2]);
    expect($result)->toEqual(1);
});

test('for() set settable and get settings', function () {
    $user = User::factory()->create();

    settings()->for($user)->set('theme', 'dark');
    expect(settings()->for($user)->get('theme'))->toBe('dark');
});

test('user() set settable', function () {
    $user = User::factory()->create();
    $this->be($user);

    settings()->user()->set('theme', 'dark');
    expect(settings()->user()->get('theme'))->toBe('dark');
    expect(settings()->my('theme'))->toBe('dark');
});

test('all() returns empty collection if table missing', function () {
    Schema::shouldReceive('hasTable', 'dropIfExists')->andReturn(false);

    expect(Settings::all())->toBeInstanceOf(Collection::class);
    expect(Settings::all())->toHaveCount(0);
});

test('sets new setting', function () {
    $this->assertDatabaseMissing('settings', ['name' => 'Settings']);

    Settings::set('name', 'Settings');

    $this->assertDatabaseHas('settings', ['name' => 'name', 'value' => 'Settings']);
});

test('ignores duplicate setting', function () {
    $this->assertDatabaseMissing('settings', ['name' => 'Settings']);

    Settings::set('name', 'Settings');
    Settings::set('name', 'Settings');

    $this->assertDatabaseHas('settings', ['name' => 'name', 'value' => 'Settings']);
    expect(Settings::all())->toHaveCount(1);

    Settings::set('email', 'Settings');
    expect(Settings::all())->toHaveCount(2);
});

test('updates existing setting', function () {
    Settings::set('name', 'Settings');
    $this->assertDatabaseHas('settings', ['name' => 'name', 'value' => 'Settings']);

    Settings::set('name', 'Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'name', 'value' => 'Laravel']);
    expect(Settings::get('name'))->toEqual('Laravel');
});

test('returns default if setting is missing', function () {
    $this->assertDatabaseMissing('settings', ['name' => 'Settings']);

    expect(Settings::get('name', 'Laravel'))->toEqual('Laravel');
});

test('retrieves saved setting', function () {
    Settings::set('name', 'Settings');

    expect(Settings::get('name', 'Laravel'))->toEqual('Settings');

    // change the setting
    Settings::set('name', 'Setting');

    expect(Settings::get('name', 'Laravel'))->toEqual('Setting');
});

test('stores multiple settings from array', function () {
    Settings::set([
        'name' => 'Settings',
        'email' => 'info@example.com',
        'tag' => 'SaaS',
    ]);

    expect(Settings::all())->toHaveCount(3);
    expect(Settings::get('name'))->toEqual('Settings');
    expect(Settings::get('email'))->toEqual('info@example.com');
    expect(Settings::get('tag'))->toEqual('SaaS');
});

test('uses helper to manage settings', function () {
    settings()->set('name', 'Settings');

    expect(settings()->get('name'))->toEqual('Settings');

    $this->assertDatabaseHas('settings', ['name' => 'name']);
});

test('uses facade to manage settings', function () {
    Settings::set('name', 'Settings');

    expect(Settings::get('name'))->toEqual('Settings');

    $this->assertDatabaseHas('settings', ['name' => 'name']);
});

test('defaults to "default" group', function () {
    settings()->set('name', 'Settings');

    $this->assertDatabaseHas('settings', [
        'name' => 'name',
        'value' => 'Settings',
        'group' => 'default',
    ]);
});

test('stores setting with custom group', function () {
    settings()->group('user')->set('name', 'Users');

    $this->assertDatabaseHas('settings', [
        'name' => 'name',
        'value' => 'Users',
        'group' => 'user',
    ]);
});

test('retrieves setting from specific group', function () {
    settings()->group('user')->set('name', 'Users');

    expect(settings()->group('user')->has('name'))->toBeTrue();
    expect(settings()->group('user')->get('name'))->toEqual('Users');
    expect(settings()->group('product')->has('name'))->toBeFalse();
});

test('returns default group settings if unspecified', function () {
    settings()->set('name', 'Settings');
    settings()->set('slug', 'settings');

    expect(settings()->all())->toHaveCount(2);
    expect(settings()->group('user')->all())->toHaveCount(0);
});

test('allows same setting in different groups', function () {
    Settings::set('name', 'Laravel');
    Settings::group('user')->set('name', 'Users');
    Settings::group('product')->set('name', 'Products');

    expect(settings()->group(['user', 'product'])->all())->toHaveCount(2);
    expect(settings()->get('name'))->toEqual('Laravel');
    expect(settings()->group('user')->get('name'))->toEqual('Users');
    expect(settings()->group('product')->get('name'))->toEqual('Products');
});

test('retrieves grouped setting via facade', function () {
    Settings::group('user')->set('name', 'Users');

    expect(Settings::group('user')->get('name'))->toEqual('Users');

    $this->assertDatabaseHas('settings', ['name' => 'name', 'group' => 'user']);
});

test('remember() returns existing setting if it exists and is truthy', function () {
    Settings::set('existing_key', 'existing_value');

    $result = Settings::remember('existing_key', 'default_value');

    expect($result)->toEqual('existing_value');
    // Should not create a new setting
    expect(Settings::all())->toHaveCount(1);
});

test('remember() sets and returns default when setting does not exist', function () {
    $this->assertDatabaseMissing('settings', ['name' => 'new_key']);

    $result = Settings::remember('new_key', 'default_value');

    expect($result)->toEqual('default_value');
    $this->assertDatabaseHas('settings', ['name' => 'new_key', 'value' => 'default_value']);
});

test('remember() sets and returns default when setting exists but is falsy', function () {
    $this->assertDatabaseMissing('settings', ['name' => 'null_key']);

    // Test with null value
    Settings::set('null_key', null);
    $result = Settings::remember('null_key', 'default_for_null');
    expect($result)->toEqual('default_for_null');
    expect(Settings::get('null_key'))->toEqual('default_for_null');

    // Test with false value
    Settings::set('false_key', false);
    // dd(Settings::all());
    $result = Settings::remember('false_key', 'default_for_false');
    expect($result)->toEqual('default_for_false');
    expect(Settings::get('false_key'))->toEqual('default_for_false');

    // Test with empty string
    Settings::set('empty_key', '');
    $result = Settings::remember('empty_key', 'default_for_empty');
    expect($result)->toEqual('default_for_empty');
    expect(Settings::get('empty_key'))->toEqual('default_for_empty');

    // Test with zero
    Settings::set('zero_key', 0);
    $result = Settings::remember('zero_key', 'default_for_zero');
    expect($result)->toEqual('default_for_zero');
    expect(Settings::get('zero_key'))->toEqual('default_for_zero');
});

test('remember() works with truthy values that might be considered falsy in other contexts', function () {
    // Test with string "0"
    Settings::set('string_zero', '0');
    $result = Settings::remember('string_zero', 'default_value');
    expect($result)->toEqual('0');

    // Test with array containing false
    Settings::set('array_with_false', [false]);
    $result = Settings::remember('array_with_false', 'default_value');
    expect($result)->toEqual([false]);
});

test('remember() works with different data types as defaults', function () {
    // Test with array default
    $result = Settings::remember('array_key', ['default', 'array']);
    expect($result)->toEqual(['default', 'array']);
    expect(Settings::get('array_key'))->toEqual(['default', 'array']);

    // Test with object default
    $object = (object) ['key' => 'value'];
    $result = Settings::remember('object_key', $object);
    expect($result)->toEqual($object);
    expect(Settings::get('object_key'))->toEqual($object);

    // Test with numeric default
    $result = Settings::remember('numeric_key', 42);
    expect($result)->toEqual(42);
    expect(Settings::get('numeric_key'))->toEqual(42);

    // Test with boolean true default
    $result = Settings::remember('bool_key', true);
    expect($result)->toEqual(true);
    expect(Settings::get('bool_key'))->toEqual(true);
});

test('remember() works with null as default', function () {
    $result = Settings::remember('null_default_key', null);

    expect($result)->toBeNull();
    expect(Settings::get('null_default_key'))->toBeNull();
    $this->assertDatabaseHas('settings', ['name' => 'null_default_key', 'value' => null]);
});

test('remember() works with groups', function () {
    // Set a value in admin group
    Settings::group('admin')->set('theme', 'admin_theme');

    // Remember should return existing value from admin group
    $result = Settings::group('admin')->remember('theme', 'default_theme');
    expect($result)->toEqual('admin_theme');

    // Remember should set default in user group (different group)
    $result = Settings::group('user')->remember('theme', 'user_default_theme');
    expect($result)->toEqual('user_default_theme');

    // Verify both groups have their own values
    expect(Settings::group('admin')->get('theme'))->toEqual('admin_theme');
    expect(Settings::group('user')->get('theme'))->toEqual('user_default_theme');
});

test('remember() works with user-specific settings', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Set a preference for user1
    Settings::for($user1)->set('preference', 'user1_pref');

    // Remember should return existing value for user1
    $result = Settings::for($user1)->remember('preference', 'default_pref');
    expect($result)->toEqual('user1_pref');

    // Remember should set default for user2 (different user)
    $result = Settings::for($user2)->remember('preference', 'user2_default');
    expect($result)->toEqual('user2_default');

    // Verify both users have their own values
    expect(Settings::for($user1)->get('preference'))->toEqual('user1_pref');
    expect(Settings::for($user2)->get('preference'))->toEqual('user2_default');
});

test('remember() using helper function', function () {
    $result = settings()->remember('helper_key', 'helper_default');

    expect($result)->toEqual('helper_default');
    expect(settings()->get('helper_key'))->toEqual('helper_default');
});

test('remember() handles complex scenarios', function () {
    // Test overwriting a falsy value with remember
    Settings::set('complex_key', false);
    expect(Settings::get('complex_key'))->toBeFalse();

    $result = Settings::remember('complex_key', 'new_value');
    expect($result)->toEqual('new_value');
    expect(Settings::get('complex_key'))->toEqual('new_value');

    // Now remember should return the truthy value
    $result = Settings::remember('complex_key', 'another_default');
    expect($result)->toEqual('new_value'); // Should return existing, not default
});
