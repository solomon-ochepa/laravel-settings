<?php

use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Models\Setting;
use SolomonOchepa\Settings\Repositories\SettingsRepository;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->settings = new SettingsRepository;
});

test('it sets a new key value in store', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    $this->settings->add('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
});

test('it dont set if same key value pair exists in store', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    $this->settings->add('app_name', 'Laravel');
    $this->settings->add('app_name', 'Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
    expect($this->settings->all(true))->toHaveCount(1);

    $this->settings->add('email_name', 'Laravel');
    expect($this->settings->all(true))->toHaveCount(2);
});

test('it updates exisiting setting if already exists', function () {
    $this->settings->add('app_name', 'Laravel');
    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);

    $this->settings->add('app_name', 'Updated Laravel');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Updated Laravel')]);
    expect($this->settings->get('app_name'))->toEqual('Updated Laravel');
});

test('it gives default value if nothing setting not found', function () {
    $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

    expect($this->settings->get('app_name', 'Default App Name'))->toEqual('Default App Name');
});

test('it gives you saved setting value', function () {
    $this->settings->add('app_name', 'Laravel');

    expect($this->settings->get('app_name', 'Default App Name'))->toEqual('Laravel');

    // change the setting
    $this->settings->add('app_name', 'Changed Laravel');

    expect($this->settings->get('app_name', 'Default App Name'))->toEqual('Changed Laravel');
});

test('it can add multiple settings in if multi array is passed', function () {
    $this->settings->add([
        'app_name' => 'Laravel',
        'app_email' => 'info@example.com',
        'app_type' => 'SaaS',
    ]);

    expect($this->settings->all())->toHaveCount(3);
    expect($this->settings->get('app_name'))->toEqual('Laravel');
    expect($this->settings->get('app_email'))->toEqual('info@example.com');
    expect($this->settings->get('app_type'))->toEqual('SaaS');
});

test('it can use helper function to set and get settings', function () {
    settings()->add('app_name', 'Cool App');

    expect(settings()->get('app_name'))->toEqual('Cool App');

    $this->assertDatabaseHas('settings', ['name' => 'app_name']);
});

test('it can access setting via facade', function () {
    Settings::add('app_name', 'Cool App');

    expect(Settings::get('app_name'))->toEqual('Cool App');

    $this->assertDatabaseHas('settings', ['name' => 'app_name']);
});

test('it has a default group name for settings', function () {
    settings()->add('app_name', 'Cool App');

    $this->assertDatabaseHas('settings', [
        'name' => 'app_name',
        'value' => json_encode('Cool App'),
        'group' => 'default',
    ]);
});

test('it can store setting with a group name', function () {
    settings()->group('set1')->add('app_name', 'Cool App');

    $this->assertDatabaseHas('settings', [
        'name' => 'app_name',
        'value' => json_encode('Cool App'),
        'group' => 'set1',
    ]);
});

test('it can get setting from a group', function () {
    settings()->group('set1')->add('app_name', 'Cool App');

    expect(settings()->group('set1')->has('app_name'))->toBeTrue();
    expect(settings()->group('set1')->get('app_name'))->toEqual('Cool App');
    expect(settings()->group('set2')->has('app_name'))->toBeFalse();
});

test('it give you all settings from default group if you dont specify one', function () {
    settings()->add('app_name', 'Cool App 1');
    settings()->add('app_name', 'Cool App 2');

    expect(settings()->all(true))->toHaveCount(1);
    expect(settings()->group('unknown')->all(true))->toHaveCount(0);
});

test('it allows same key to be used in different groups', function () {
    settings()->group('team1')->add('app_name', 'Cool App 1');
    settings()->group('team2')->add('app_name', 'Cool App 2');

    expect(settings()->all(true))->toHaveCount(2);
    expect(settings()->group('team1')->get('app_name'))->toEqual('Cool App 1');
    expect(settings()->group('team2')->get('app_name'))->toEqual('Cool App 2');
});

test('it get group settings using facade', function () {
    Settings::group('team1')->add('app_name', 'Cool App');

    expect(Settings::group('team1')->get('app_name'))->toEqual('Cool App');

    $this->assertDatabaseHas('settings', ['name' => 'app_name', 'group' => 'team1']);
});
