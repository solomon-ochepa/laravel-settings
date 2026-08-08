<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use SolomonOchepa\Settings\Models\Setting;
use SolomonOchepa\Settings\Tests\App\Models\User;

it('scopeGroup() and scopeFor() return a Builder for chaining', function (Closure $call) {
    expect($call())->toBeInstanceOf(Builder::class);
})->with([
    'scopeGroup()' => [fn () => Setting::query()->group('admin')],
    'scopeFor()' => [fn () => Setting::query()->for(User::class)],
]);

describe('scopeGroup()', function () {
    it('filters by a single group', function () {
        $query = Setting::query()->group('admin');

        expect($query->toSql())->toContain('"group" in (?)');
        expect($query->getBindings())->toEqual(['admin']);
    });

    it('filters by multiple groups', function () {
        $query = Setting::query()->group(['admin', 'user']);

        expect($query->toSql())->toContain('"group" in (?, ?)');
        expect($query->getBindings())->toEqual(['admin', 'user']);
    });
});

describe('scopeFor()', function () {
    it('filters by a settable class-string with no id', function () {
        $query = Setting::query()->for(User::class);

        // A null id becomes a "IS NULL" clause, not a bound parameter.
        expect($query->getBindings())->toEqual([User::class]);
        expect($query->toSql())->toContain('"settable_id" is null');
    });

    it('filters by a settable entity\'s class and id', function () {
        // make(), not create(): this only asserts the query builder's
        // bindings, so it must not touch the database.
        $entity = User::factory()->make(['id' => 'abc-123']);

        $query = Setting::query()->for($entity);

        expect($query->getBindings())->toEqual([User::class, 'abc-123']);
    });
});
