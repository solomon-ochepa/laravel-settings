<?php

use SolomonOchepa\Settings\Facades\Settings;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {});

test('it sets a new key value', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    Settings::set('app_name', 'Laravel');

    // $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
});

test('it dont set if same key value pair exists', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    Settings::set('app_name', 'Laravel');
    Settings::set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
    expect(Settings::all())->toHaveCount(1);

    Settings::set('email_name', 'Laravel');
    expect(Settings::all())->toHaveCount(2);
});

test('it updates exisiting setting if already exists', function () {
    Settings::set('app_name', 'Laravel');
    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);

    Settings::set('app_name', 'Updated Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Updated Laravel')]);
    expect(Settings::get('app_name'))->toEqual('Updated Laravel');
});

test('it gives default value if setting is not found', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    expect(Settings::get('app_name', 'Default App Name'))->toEqual('Default App Name');
});

test('it gives you saved setting value', function () {
    Settings::set('app_name', 'Laravel');

    expect(Settings::get('app_name', 'Default App Name'))->toEqual('Laravel');

    // change the setting
    Settings::set('app_name', 'Changed Laravel');

    expect(Settings::get('app_name', 'Default App Name'))->toEqual('Changed Laravel');
});

test('it can add multiple settings in if multi array is passed', function () {
    Settings::set([
        'app_name' => 'Laravel',
        'app_email' => 'info@example.com',
        'app_type' => 'SaaS',
    ]);

    expect(Settings::all())->toHaveCount(3);
    expect(Settings::get('app_name'))->toEqual('Laravel');
    expect(Settings::get('app_email'))->toEqual('info@example.com');
    expect(Settings::get('app_type'))->toEqual('SaaS');
});

test('it can use helper function to set and get settings', function () {
    settings()->set('app_name', 'Laravel');

    expect(settings()->get('app_name'))->toEqual('Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name']);
});

test('it can access setting via facade', function () {
    Settings::add('app_name', 'Laravel');

    expect(Settings::get('app_name'))->toEqual('Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name']);
});

test('it has a default group name for settings', function () {
    settings()->set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', [
        'name' => 'app_name',
        'value' => json_encode('Laravel'),
        'group' => 'default',
    ]);
});

test('it can store setting with a group name', function () {
    settings()->group('set1')->set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', [
        'name' => 'app_name',
        'value' => json_encode('Laravel'),
        'group' => 'set1',
    ]);
});

test('it can get setting from a group', function () {
    settings()->group('set1')->set('app_name', 'Laravel');

    expect(settings()->group('set1')->has('app_name'))->toBeTrue();
    expect(settings()->group('set1')->get('app_name'))->toEqual('Laravel');
    expect(settings()->group('set2')->has('app_name'))->toBeFalse();
});

test('it give you all settings from default group if you dont specify one', function () {
    settings()->set('app_name', 'Laravel 1');
    settings()->set('app_name', 'Laravel 2');

    expect(settings()->all())->toHaveCount(1);
    expect(settings()->group('unknown')->all())->toHaveCount(0);
});

test('it allows same key to be used in different groups', function () {
    Settings::set('app_name', 'Laravel');
    Settings::group('team1')->set('app_name', 'Laravel 1');
    Settings::group('team2')->set('app_name', 'Laravel 2');

    // dd(Settings::group(['team1', 'team2'])->all());
    expect(settings()->group(['team1', 'team2'])->all())->toHaveCount(2);
    expect(settings()->group('team1')->get('app_name'))->toEqual('Laravel 1');
    expect(settings()->group('team2')->get('app_name'))->toEqual('Laravel 2');
});

test('it get group settings using facade', function () {
    Settings::group('team1')->set('app_name', 'Laravel');

    expect(Settings::group('team1')->get('app_name'))->toEqual('Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'group' => 'team1']);
});
