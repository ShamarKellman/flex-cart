# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

FlexCart is a Laravel package (PHP 8.3+, Laravel 11/12) for shopping cart functionality. It uses `moneyphp/money ^4.0` for financial precision (all amounts stored in cents), supports session and database storage backends, and is built following Spatie's package development patterns.

## Commands

```bash
# Run all tests
composer test

# Run a specific test by name or class
composer test -- --filter=TestName
composer test -- tests/FlexCartTest.php

# Run tests with coverage
composer test-coverage

# Static analysis (PHPStan level 5 + Larastan)
composer analyse

# Format code (Laravel Pint, enforces strict_types)
composer format

# Prepare testbench environment
composer prepare
```

## Architecture

### Core Design

The main `FlexCart` class (`src/FlexCart.php`) implements `CartInterface` and composes three traits:
- `HasItems` — add/remove/update cart items
- `HasCoupons` — apply/remove coupons and calculate discounts
- `HasShipping` — set shipping address and cost

Access is via a Laravel Facade (`Facades/FlexCart.php`).

### Storage Abstraction

`CartStorageInterface` has two implementations:
- `SessionStorage` — stores cart in Laravel session
- `DatabaseStorage` — persists to `carts`/`cart_items` tables

Driver is selected via `CART_STORAGE_DRIVER` env var (default: `session`).

### Coupon System

Three classes handle coupons:
- `CouponManager` — manages stacking rules and applies multiple coupons
- `CouponCalculator` — calculates fixed/percentage/shipping discounts
- `CouponRepository` — database queries

Supported types: fixed amount, percentage, shipping discount. Coupons track usage limits, expiry, and per-product rules.

### Money Handling

All prices use `Money\Money` objects. `Calculator` (`src/Money/Calculator.php`) handles arithmetic. `MoneyCast` (`src/Money/MoneyCast.php`) is an Eloquent cast for serializing Money to/from integer cents in the database. Never mix currencies — `CurrencyMismatchException` is thrown on mismatches.

### Events

All cart operations dispatch events extending `CartEvent`. Events: `ItemAddedToCart`, `ItemRemovedFromCart`, `QuantityUpdated`, `CartCleared`, `ShippingCostSet`, `ShippingAddressSet`, `CouponApplied`, `CouponRemoved`, `CouponExpired`.

### Exceptions

All exceptions extend `CartException`. Use static factory methods:
```php
throw InvalidQuantityException::tooLow($quantity);
throw ProductNotBuyableException::fromModel($model);
throw CurrencyMismatchException::create('USD', 'EUR', 'context');
```

### BuyableInterface

Products added to the cart must implement `BuyableInterface` (from `src/Contracts/`). See `tests/Models/Product.php` for the reference implementation.

## Code Standards

- All files use `declare(strict_types=1)` (enforced by Pint)
- PHPStan level 5 must pass: run `composer analyse` after changes
- Tests use Pest 3 with `it('description', fn() => ...)` syntax and `beforeEach` setup
- Tests extend `Tests\TestCase` (Orchestra Testbench), which sets up in-memory SQLite with all package tables

## Database Schema

Two migration files in `database/migrations/`:
1. `create_flex_cart_tables` — `carts`, `cart_items`, `shipping_details`
2. `create_coupons_tables` — `coupons`, `coupon_usages`

Models: `Cart`, `CartItem`, `ShippingDetail`, `Coupon`, `CouponUsage` (all in `src/Models/`).

## Configuration

Published config at `config/flex-cart.php`. Key env vars:
```
CART_DEFAULT_CURRENCY=USD
CART_TAX_RATE=0.0
CART_STORAGE_DRIVER=session  # or "database"
```

## Testing Notes

- `tests/TestCase.php` creates in-memory SQLite tables for `products`, `users`, and all package tables in `getEnvironmentSetUp()`
- `tests/Models/Product.php` is the test fixture implementing `BuyableInterface`
- PHPUnit is configured via `phpunit.xml.dist`; `failOnWarning` and `failOnRisky` are enabled
- CI tests against PHP 8.3/8.4 × Laravel 11/12 × prefer-lowest/prefer-stable on Ubuntu and Windows
