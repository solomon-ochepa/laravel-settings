<?php

namespace SolomonOchepa\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Models\Setting;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepository $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = new SettingsRepository;
    }

    public function test_it_sets_a_new_key_value_in_store()
    {
        $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

        $this->settings->add('app_name', 'Laravel');

        $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
    }

    public function test_it_dont_set_if_same_key_value_pair_exists_in_store()
    {
        $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

        $this->settings->add('app_name', 'Laravel');
        $this->settings->add('app_name', 'Laravel');

        $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
        $this->assertCount(1, $this->settings->all(true));

        $this->settings->add('email_name', 'Laravel');
        $this->assertCount(2, $this->settings->all(true));
    }

    public function test_it_updates_exisiting_setting_if_already_exists()
    {
        $this->settings->add('app_name', 'Laravel');
        $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);

        $this->settings->add('app_name', 'Updated Laravel');

        $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Updated Laravel')]);
        $this->assertEquals('Updated Laravel', $this->settings->get('app_name'));
    }

    // public function test_it_removes_a_setting_from_storage()
    // {
    //     $this->settings->add('app_name', 'Laravel');
    //     $this->assertDatabaseHas('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
    //     $this->assertEquals('Laravel', $this->settings->get('app_name'));

    //     $this->settings->delete('app_name');

    //     $this->assertDatabaseMissing('settings', ['name' => 'app_name', 'value' => json_encode('Laravel')]);
    //     $this->assertNull($this->settings->get('app_name'));
    // }

    public function test_it_gives_default_value_if_nothing_setting_not_found()
    {
        $this->assertDatabaseMissing('settings', ['app_name' => 'Laravel']);

        $this->assertEquals(
            'Default App Name',
            $this->settings->get('app_name', 'Default App Name')
        );
    }

    public function test_it_gives_you_saved_setting_value()
    {
        $this->settings->add('app_name', 'Laravel');

        $this->assertEquals(
            'Laravel',
            $this->settings->get('app_name', 'Default App Name')
        );

        // change the setting
        $this->settings->add('app_name', 'Changed Laravel');

        $this->assertEquals(
            'Changed Laravel',
            $this->settings->get('app_name', 'Default App Name')
        );
    }

    public function test_it_can_add_multiple_settings_in_if_multi_array_is_passed()
    {
        $this->settings->add([
            'app_name' => 'Laravel',
            'app_email' => 'info@example.com',
            'app_type' => 'SaaS',
        ]);

        $this->assertCount(3, $this->settings->all());
        $this->assertEquals('Laravel', $this->settings->get('app_name'));
        $this->assertEquals('info@example.com', $this->settings->get('app_email'));
        $this->assertEquals('SaaS', $this->settings->get('app_type'));
    }

    public function test_it_can_use_helper_function_to_set_and_get_settings()
    {
        settings()->set('app_name', 'Cool App');

        $this->assertEquals('Cool App', settings()->get('app_name'));

        $this->assertDatabaseHas('settings', ['name' => 'app_name']);
    }

    public function test_it_can_access_setting_via_facade()
    {
        Settings::set('app_name', 'Cool App');

        $this->assertEquals('Cool App', Settings::get('app_name'));

        $this->assertDatabaseHas('settings', ['name' => 'app_name']);
    }

    public function test_it_has_a_default_group_name_for_settings()
    {
        settings()->set('app_name', 'Cool App');

        $this->assertDatabaseHas('settings', [
            'name' => 'app_name',
            'value' => json_encode('Cool App'),
            'group' => 'default',
        ]);
    }

    public function test_it_can_store_setting_with_a_group_name()
    {
        settings()->group('set1')->set('app_name', 'Cool App');

        $this->assertDatabaseHas('settings', [
            'name' => 'app_name',
            'value' => json_encode('Cool App'),
            'group' => 'set1',
        ]);
    }

    public function test_it_can_get_setting_from_a_group()
    {
        settings()->group('set1')->set('app_name', 'Cool App');

        $this->assertTrue(settings()->group('set1')->has('app_name'));
        $this->assertEquals('Cool App', settings()->group('set1')->get('app_name'));
        $this->assertFalse(settings()->group('set2')->has('app_name'));
    }

    public function test_it_give_you_all_settings_from_default_group_if_you_dont_specify_one()
    {
        settings()->set('app_name', 'Cool App 1');
        settings()->set('app_name', 'Cool App 2');

        $this->assertCount(1, settings()->all(true));
        $this->assertCount(0, settings()->group('unknown')->all(true));
    }

    // public function test_it_allows_same_key_to_be_used_in_different_groups()
    // {
    //     settings()->group('team1')->set('app_name', 'Cool App 1');
    //     settings()->group('team2')->set('app_name', 'Cool App 2');

    //     $this->assertCount(2, Setting::all());
    //     $this->assertEquals('Cool App 1', settings()->group('team1')->get('app_name'));
    //     $this->assertEquals('Cool App 2', settings()->group('team2')->get('app_name'));
    // }

    /**
     * it get group settings using facade
     *
     * @test
     */
    public function test_it_get_group_settings_using_facade()
    {
        Settings::group('team1')->set('app_name', 'Cool App');

        $this->assertEquals('Cool App', Settings::group('team1')->get('app_name'));

        $this->assertDatabaseHas('settings', ['name' => 'app_name', 'group' => 'team1']);
    }
}
