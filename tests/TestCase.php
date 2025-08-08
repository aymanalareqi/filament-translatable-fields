<?php

namespace Alareqi\FilamentTranslatableFields\Tests;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            FilamentTranslatableFieldsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.locale', 'en');
        config()->set('app.supported_locales', ['en', 'ar', 'fr']);
    }
}
