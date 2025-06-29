<?php

use SolomonOchepa\Settings\Facades\Settings;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {});

test('sets new setting', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    Settings::set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => 'Laravel']);
});

test('ignores duplicate setting', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    Settings::set('app_name', 'Laravel');
    Settings::set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => 'Laravel']);
    expect(Settings::all())->toHaveCount(1);

    Settings::set('email_name', 'Laravel');
    expect(Settings::all())->toHaveCount(2);
});

test('updates existing setting', function () {
    Settings::set('app_name', 'Laravel');
    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => 'Laravel']);

    Settings::set('app_name', 'Updated Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => 'Updated Laravel']);
    expect(Settings::get('app_name'))->toEqual('Updated Laravel');
});

test('returns default if setting is missing', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    expect(Settings::get('app_name', 'Default App Name'))->toEqual('Default App Name');
});

test('retrieves saved setting', function () {
    Settings::set('app_name', 'Laravel');

    expect(Settings::get('app_name', 'Default App Name'))->toEqual('Laravel');

    // change the setting
    Settings::set('app_name', 'Changed Laravel');

    expect(Settings::get('app_name', 'Default App Name'))->toEqual('Changed Laravel');
});

test('stores multiple settings from array', function () {
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

test('uses helper to manage settings', function () {
    settings()->set('app_name', 'Laravel');

    expect(settings()->get('app_name'))->toEqual('Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name']);
});

test('uses facade to manage settings', function () {
    Settings::set('app_name', 'Laravel');

    expect(Settings::get('app_name'))->toEqual('Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name']);
});

test('defaults to "default" group', function () {
    settings()->set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', [
        'name' => 'app_name',
        'value' => 'Laravel',
        'group' => 'default',
    ]);
});

test('stores setting with custom group', function () {
    settings()->group('set1')->set('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', [
        'name' => 'app_name',
        'value' => 'Laravel',
        'group' => 'set1',
    ]);
});

test('retrieves setting from specific group', function () {
    settings()->group('set1')->set('app_name', 'Laravel');

    expect(settings()->group('set1')->has('app_name'))->toBeTrue();
    expect(settings()->group('set1')->get('app_name'))->toEqual('Laravel');
    expect(settings()->group('set2')->has('app_name'))->toBeFalse();
});

test('returns default group settings if unspecified', function () {
    settings()->set('app_name', 'Laravel 1');
    settings()->set('app_name', 'Laravel 2');

    expect(settings()->all())->toHaveCount(1);
    expect(settings()->group('unknown')->all())->toHaveCount(0);
});

test('allows same setting in different groups', function () {
    Settings::set('app_name', 'Laravel');
    Settings::group('team1')->set('app_name', 'Laravel 1');
    Settings::group('team2')->set('app_name', 'Laravel 2');

    // dd(Settings::group(['team1', 'team2'])->all());
    // expect(settings()->group(['team1', 'team2'])->all())->toHaveCount(2);
    expect(settings()->group('team1')->get('app_name'))->toEqual('Laravel 1');
    expect(settings()->group('team2')->get('app_name'))->toEqual('Laravel 2');
});

test('retrieves grouped setting via facade', function () {
    Settings::group('team1')->set('app_name', 'Laravel');

    expect(Settings::group('team1')->get('app_name'))->toEqual('Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'group' => 'team1']);
});
