<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\Tests\App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {});

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
    $repo = new SettingsRepository;
    $repo->flush();
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

test('group() returns default and sets group', function () {
    expect(Settings::group())->toEqual('default');

    Settings::group('user');
    expect(Settings::group())->toEqual(['user']);

    Settings::group(['a', 'b']);
    expect(Settings::group())->toEqual(['a', 'b']);
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

    // dd(Settings::group(['default', 'user', 'product'])->all());

    // expect(settings()->group(['user', 'product'])->all())->toHaveCount(2);
    expect(settings()->group('user')->get('name'))->toEqual('Users');
    expect(settings()->group('product')->get('name'))->toEqual('Products');
});

test('retrieves grouped setting via facade', function () {
    Settings::group('user')->set('name', 'Users');

    expect(Settings::group('user')->get('name'))->toEqual('Users');

    $this->assertDatabaseHas('settings', ['name' => 'name', 'group' => 'user']);
});
