# laravel-settings

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solomon-ochepa/laravel-settings.svg)](https://packagist.org/packages/solomon-ochepa/laravel-settings)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/solomon-ochepa/laravel-settings.svg)](https://packagist.org/packages/solomon-ochepa/laravel-settings)

Store settings as key-value pairs in the database.

> All settings are cached to improve performance by reducing SQL queries to zero. The cache is refreshed automatically whenever a setting is written, trashed, restored, or deleted, so you never read stale data.

## Installation

You can install the package via composer:

```bash
composer require solomon-ochepa/laravel-settings
```

### Laravel 5.4
If you are installing on Laravel 5.4 or lower, you will need to manually register the Service Provider by adding it to the `providers` array and the Facade to the `aliases` array in `config/app.php`.

```php
'providers' => [
    //...
    SolomonOchepa\Settings\SettingsServiceProvider::class
]

'aliases' => [
    //...
    "Settings" => SolomonOchepa\Settings\Facades\Settings::class
]
```

In Laravel 5.5 or above, the service provider automatically gets registered, and the `Settings` facade will be available immediately.

Get started with `Settings::all()`.

## Migration
Optionally, you can publish the migration file by running:
```
php artisan vendor:publish --provider="SolomonOchepa\Settings\SettingsServiceProvider" --tag="migrations"
```

Now, run `php artisan migrate` to migrate the settings table.

## Getting Started
You can utilize the Laravel settings package using either the helper function `settings()` or the facade `Settings::all()`.

## Methods
#### `all()`
Get all settings in the current group/settable scope as a key/value collection.
```php
settings();
// or
settings()->all();
// or
Settings::all();
```

#### `get()`
Get a specific setting
```php
settings($key, $default = null);
// or
settings()->get($key, $default = null);
// or
Settings::get($key, $default = null);
```

#### `my()`
Get a setting scoped to the `auth()` user.
```php
settings()->my($key, $default = null);
// or
Settings::my($key, $default = null);
```

#### `remember()`
Get a setting, or set and return `$default` if it doesn't already exist. An existing falsy value (`null`, `false`, `0`, `''`) is treated as present and returned as-is, not overwritten.
```php
settings()->remember($key, $default);
// or
Settings::remember($key, $default);
```

#### `set()`
Set a specific setting
```php
settings([$key => $value]);
// or
settings()->set($key, $value);
// or
Settings::set($key, $value);
```

// Set a multiple settings
```php
settings([$key => $value, $key2 => $value2]);
// or
settings()->set([
   $key => $value,
   $key2 => $value2,
]);
// or
Settings::set([
   $key => $value,
   $key2 => $value2,
]);
```

#### `has()`
Check if a setting key exists
```php
settings()->has($key);
// or
Settings::has($key);
```

#### `trash()`
Soft-delete a setting
```php
settings()->trash($key);
// or
Settings::trash($key);
```

#### `restore()`
Restore a soft-deleted (trashed) setting
```php
settings()->restore($key);
// or
Settings::restore($key);
```

#### `delete()`
Permanently delete a trashed setting
```php
settings()->delete($key);
// or
Settings::delete($key);
```

## Groups
You can organize your settings into groups.

> If you are upgrading from a previous version, don't forget to run the migration.

Initiate grouping by chaining the `group()` method:

```php
// Save setting
settings()->group($name)->set($key, $value);

// Get setting
settings()->group($name)->get($key);
```

Pass an array of group names to read from several groups at once. In this case, `all()` returns a collection keyed by group name instead of a flat key/value collection:

```php
settings()->group(['admin', 'user'])->all();
// => ['admin' => [...], 'user' => [...]]
```

## Settable `for()`
Get/set settings for a specific entity (e.g. an Eloquent model instance).
```php
Settings::for($settable)->set($key, $value)

// helper function
settings()->for($settable)->set($key, $value)

// Example:
settings()->for(auth()->user())->set($key, $value);
```

Passing a falsy value (e.g. `null`), or no argument at all, clears the scope, so subsequent calls read/write the global, unscoped settings:
```php
settings()->for(null)->get($key); // reads the global setting, not tied to any entity
settings()->for()->get($key); // same as above
```

## Settable `user()`
Bind settings to the `auth()` user. If there is no authenticated user (e.g. a guest request), this falls back to the global, unscoped settings instead of throwing.
```php
settings()->user()->all();
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

### Testing

The package contains some integration/smoke tests, set up with Orchestra. The tests can be run via phpunit.

```bash
$ composer test
```

### Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security-related issues, please email solomonochepa@gmail.com instead of using the issue tracker.

### Credits

- ...

## About "Oki Technologies Ltd"
Oki Technologies, https://www.okitechnologies.com.ng is a dynamic IT firm dedicated to delivering cutting-edge solutions in software development and related services. With a passion for innovation and a commitment to excellence, Oki Technologies leverages the latest technologies and industry best practices to craft tailored solutions that meet the unique needs of each client.

From web and mobile application development to custom software solutions, Oki Technologies offers a comprehensive suite of services designed to empower businesses and organizations across various industries. With a team of skilled professionals, Oki Technologies combines technical expertise with creative insights to deliver high-quality, scalable, and user-friendly software solutions.

At Oki Technologies, we prioritize customer satisfaction and strive to build long-term partnerships with our clients. Our collaborative approach ensures that we understand our clients' goals and objectives, allowing us to deliver solutions that drive tangible results and add value to their businesses.

Whether you're a startup looking to launch a digital product or an established enterprise seeking to optimize your existing software infrastructure, Oki Technologies is your trusted partner for all your software development needs. Let us help you turn your ideas into reality and propel your business to new heights in the digital age.

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
