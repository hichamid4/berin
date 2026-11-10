<?php

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run database-resetting tests against any database that is not a dedicated
     * "*_testing" database, so the local demonstration database can never be wiped.
     * Tests that use DatabaseTransactions only roll back their own changes and remain allowed.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        $uses = class_uses_recursive(static::class);
        $resetsDatabase = isset($uses[RefreshDatabase::class]) || isset($uses[LazilyRefreshDatabase::class]);
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if ($resetsDatabase && ! str_ends_with($database, '_testing') && $database !== ':memory:') {
            $this->markTestSkipped("Skipped: this test resets the database and \"{$database}\" is not a *_testing database.");
        }

        return parent::setUpTraits();
    }
}
