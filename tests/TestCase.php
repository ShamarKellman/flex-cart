<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use ShamarKellman\FlexCart\FlexCartServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'ShamarKellman\\FlexCart\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            FlexCartServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        Schema::create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        $migration = include __DIR__.'/../database/migrations/2024_01_01_000000_create_flex_cart_tables.php';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/2024_01_01_000001_create_coupons_tables.php';
        $migration->up();
    }
}
