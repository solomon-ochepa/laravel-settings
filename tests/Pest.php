<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
| Unit tests still need the Testbench app (container/config) to construct
| repositories and resolve models, but must never touch the database —
| that's what keeps them fast and what separates them from Feature tests.
| RefreshDatabase is intentionally NOT applied here.
*/

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Global Setup
|--------------------------------------------------------------------------
|
| Applies to every test in the suite, so individual test files don't need
| to repeat it. The cache store (array driver) persists across tests within
| the same process, unlike the database, so it must be reset explicitly.
|
*/

beforeEach(function () {
    Cache::flush();
});

/*
|--------------------------------------------------------------------------
| Datasets
|--------------------------------------------------------------------------
|
| Named datasets registered here can be reused across test files via
| ->with('dataset name').
|
*/

dataset('falsy setting values', [
    'null' => [null],
    'false' => [false],
    'empty string' => [''],
    'zero' => [0],
]);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something() {}

/**
 * Invoke the protected SettingsRepository::cache_key() method for assertions.
 * Shared by both the Unit (key-generation) and Feature (behavioral) tests.
 */
function cacheKeyOf(SettingsRepository $repository, ?string $key = null): string
{
    $method = (new ReflectionClass($repository))->getMethod('cache_key');
    $method->setAccessible(true);

    return $method->invoke($repository, $key);
}
