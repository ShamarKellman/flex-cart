<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart;

use Illuminate\Foundation\Application;
use ShamarKellman\FlexCart\Contracts\CartStorageInterface;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FlexCartServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('flex-cart')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations('create_flex_cart_table', 'create_coupons_tables');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(CartStorageInterface::class, function (Application $app) {
            $driver = config('flex-cart.storage.driver', 'session');
            $class = config("flex-cart.storage.drivers.{$driver}");

            return new $class;
        });

        $this->app->bind(FlexCart::class, function ($app) {
            return new FlexCart($app->make(CartStorageInterface::class));
        });

        $this->app->alias(FlexCart::class, 'flex-cart');
    }
}
