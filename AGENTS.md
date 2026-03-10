# FlexCart Agent Guidelines

This file provides comprehensive guidelines for agentic coding agents working with the FlexCart Laravel shopping cart package.

## 🏗 Project Overview

**FlexCart** is a flexible shopping cart package for Laravel 12+ that provides:
- Multiple storage backends (session/database)
- Tax calculations with configurable rates
- Money PHP integration for accurate financial calculations
- Comprehensive event system for cart lifecycle hooks
- Shipping address and cost management
- Custom exception handling with detailed error messages
- Full test suite with 49 tests covering all functionality

## 📁 Directory Structure

```
flex-cart/
├── src/
│   ├── Contracts/          # Interface definitions
│   │   ├── BuyableInterface.php
│   │   ├── CartInterface.php
│   │   ├── CartItemInterface.php
│   │   └── CartStorageInterface.php
│   ├── Events/             # Event system
│   │   ├── CartEvent.php (base)
│   │   ├── ItemAddedToCart.php
│   │   ├── ItemRemovedFromCart.php
│   │   ├── QuantityUpdated.php
│   │   ├── CartCleared.php
│   │   ├── ShippingCostSet.php
│   │   └── ShippingAddressSet.php
│   ├── Exceptions/         # Custom exception classes
│   │   ├── CartException.php (base)
│   │   ├── CartItemNotFoundException.php
│   │   ├── CurrencyMismatchException.php
│   │   ├── InvalidQuantityException.php
│   │   └── ProductNotBuyableException.php
│   ├── Models/              # Eloquent models
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   └── ShippingDetail.php
│   ├── Money/               # Money utilities
│   │   ├── Calculator.php
│   │   └── MoneyCast.php
│   ├── Storage/             # Storage backends
│   │   ├── DatabaseStorage.php
│   │   └── SessionStorage.php
│   ├── Facades/            # Laravel facade
│   │   └── FlexCart.php
│   └── FlexCart.php        # Main cart implementation
├── tests/                 # Test suite
│   ├── FlexCartTest.php
│   ├── EventTest.php
│   ├── ExceptionTest.php
│   ├── Models/Product.php
│   └── TestCase.php
├── config/                # Package configuration
│   └── flex-cart.php
├── database/migrations/     # Database schema
└── database/factories/    # Test factories
```

## 🛠️ Build & Development Commands

### Essential Commands

```bash
# Install dependencies
composer install

# Run test suite
composer test                    # Run all tests
composer test --filter=TestName  # Run specific test
composer test --filter="EventTest" # Run event tests only
composer test-coverage         # Run tests with coverage report

# Code quality checks
composer analyse               # Run PHPStan static analysis
composer format                # Format code with Laravel Pint

# Development setup
composer prepare                # Prepare Laravel testbench environment

# Individual test commands
vendor/bin/pest tests/FlexCartTest/it_can_add_item_to_cart
vendor/bin/pest tests/EventTest/it_dispatches_item_added_event
```

### Test Execution Guidelines

- **Always run full test suite** before major changes: `composer test`
- **Use test filtering** for targeted debugging: `composer test --filter=specific_test`
- **Check coverage** after adding features: `composer test-coverage`
- **Run static analysis** after changes: `composer analyse`

## 📋 Code Style & Standards

### PHP Standards Compliance

✅ **Strict Types**: All files use `declare(strict_types=1)`
✅ **PHP 8.3+ Compatibility**: Modern PHP features used appropriately
✅ **PSR-4 Autoloading**: Standard namespace and class loading
✅ **Laravel Conventions**: Follows Laravel package development patterns

### Import Organization

```php
<?php

// Always include this order and grouping:

// 1. Standard library imports (no vendor prefixes)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Money\Money;
use Money\Currency;

// 2. Contract interfaces
use ShamarKellman\FlexCart\Contracts\BuyableInterface;
use ShamarKellman\FlexCart\Contracts\CartInterface;

// 3. Package-specific imports
use ShamarKellman\FlexCart\Events\ItemAddedToCart;
use ShamarKellman\FlexCart\Events\QuantityUpdated;
use ShamarKellman\FlexCart\Exceptions\CartException;
```

### Class Structure Template

```php
<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\[Component];

use [RelevantImports];

/**
 * Brief description of class purpose
 *
 * @template TModel of Model
 */
class [ClassName] extends [ParentClass] implements [Interface]
{
    /**
     * Class properties with PHPDoc
     */
    protected Type $property;

    /**
     * Constructor with type hints
     *
     * @param Type $param Description
     */
    public function __construct(Type $param)
    {
        // Implementation
    }

    /**
     * Public method with return type
     *
     * @return ReturnType Description
     */
    public function methodName(): ReturnType
    {
        // Implementation
    }
}
```

### Naming Conventions

**Classes**: `PascalCase` (e.g., `FlexCart`, `CartItem`)
**Methods**: `camelCase` (e.g., `addItem`, `updateItem`)
**Properties**: `camelCase` (e.g., `sessionKey`, `cart`)
**Constants**: `UPPER_SNAKE_CASE` (e.g., `DEFAULT_CURRENCY`)
**Interfaces**: `PascalCase` with `Interface` suffix

### Documentation Standards

```php
/**
 * Class summary
 *
 * @template TModel of Model
 * @implements InterfaceName
 */
class ClassName
{
    /**
     * Property description with type
     */
    protected Type $property;

    /**
     * Method description
     *
     * @param ParamType $param Description
     * @return ReturnType Description
     * @throws ExceptionType When thrown
     */
    public function methodName(ParamType $param): ReturnType
    {
        // Implementation
    }
}
```

## 🧪 Error Handling Patterns

### Custom Exception Hierarchy

```php
try {
    // Cart operation
} catch (CartException $e) {
    // Handle specific cart exceptions
} catch (\Throwable $e) {
    // Handle unexpected errors
}
```

### Exception Creation Guidelines

```php
// Use specific exceptions instead of generic ones
throw InvalidQuantityException::tooLow($quantity);
throw ProductNotBuyableException::fromModel($model);
throw CurrencyMismatchException::create('USD', 'EUR', 'shipping cost');
```

### Validation Patterns

```php
// Always validate inputs early
if ($quantity < 1) {
    throw InvalidQuantityException::tooLow($quantity);
}

if (!$buyable instanceof BuyableInterface) {
    throw ProductNotBuyableException::fromModel($buyable);
}

// Validate Money objects explicitly
$this->validateCurrency($money, 'context');
```

## 🎯 Event System Integration

### Event Types Available

1. **`ItemAddedToCart`**: New item added to cart
2. **`QuantityUpdated`**: Item quantity modified
3. **`ItemRemovedFromCart`**: Item removed from cart
4. **`CartCleared`**: Entire cart cleared
5. **`ShippingCostSet`**: Shipping cost changed
6. **`ShippingAddressSet`**: Shipping address updated

### Event Listening Patterns

```php
// Register event listeners in service provider
Event::listen(
    ItemAddedToCart::class,
    [App\Listeners\LogCartItemAdded::class, 'handle']
);

// Closure-based listeners
Event::listen(ItemAddedToCart::class, function ($event) {
    logger()->info('Item added', $event->getCartItem()->toArray());
});
```

### Event Data Access

```php
// All events provide rich context
$cartItem = $event->getCartItem();
$quantity = $event->getNewQuantity();
$buyable = $event->getBuyable();
$unitPrice = $event->getUnitPrice();
$options = $event->getOptions();
```

## 🗄️ Database & Migrations

### Migration Standards

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            $table->foreignId('related_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('options')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['foreign_id', 'created_at']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### Model Conventions

```php
// Use proper Eloquent conventions
protected $fillable = [
    'field1',
    'field2',
];

protected $casts = [
    'json_field' => 'array',
    'money_field' => MoneyCast::class,
];

// Relationships
public function relatedModel(): BelongsTo
{
    return $this->belongsTo(RelatedModel::class);
}
```

## 🧪 Testing Guidelines

### Test Structure

```php
<?php

declare(strict_types=1);

use Tests\TestCase;
use ShamarKellman\FlexCart\Facades\FlexCart;
use ShamarKellman\FlexCart\Tests\Models\Product;

beforeEach(function () {
    // Setup test data
    $this->product = Product::create([
        'name' => 'Test Product',
        'price' => 1000,
    ]);
});

it('describes specific behavior', function () {
    // Test implementation
    expect($result)->toBe($expected);
});
```

### Test Organization

- **Unit Tests**: Individual class/method testing
- **Feature Tests**: Integration testing across components
- **Exception Tests**: Error handling validation
- **Event Tests**: Event dispatching verification

### Test Naming

```php
// Descriptive test names
it('can_add_item_to_cart', function () { });
it('throws_exception_when_quantity_is_invalid', function () { });
it('dispatches_event_when_item_added', function () { });
it('calculates_total_with_tax_correctly', function () { });
```

### Mock and Factory Patterns

```php
// Use Laravel factories for test data
$product = Product::factory()->create();

// Event faking
Event::fake();

// Assert event dispatching
Event::assertDispatched(ItemAddedToCart::class, function ($event) {
    return $event->getCartItem()->id === $expectedId;
});
```

## 🔧 Configuration Management

### Configuration Structure

```php
// config/flex-cart.php
return [
    'default_currency' => env('CART_DEFAULT_CURRENCY', 'USD'),
    'tax_rate' => env('CART_TAX_RATE', 0),
    
    'storage' => [
        'driver' => env('CART_STORAGE_DRIVER', 'session'),
        'drivers' => [
            'session' => \ShamarKellman\FlexCart\Storage\SessionStorage::class,
            'database' => \ShamarKellman\FlexCart\Storage\DatabaseStorage::class,
        ],
    ],
];
```

### Environment Variables

```env
CART_DEFAULT_CURRENCY=USD
CART_TAX_RATE=8.25
CART_STORAGE_DRIVER=session
```

## 🚀 Performance Considerations

### Memory Management

- **Collection Usage**: Use collections for cart items, avoid array operations
- **Lazy Loading**: Only load relationships when needed
- **Event Data**: Keep event payloads lightweight
- **Storage Strategy**: Choose session vs database based on use case

### Database Optimization

- **Indexes**: Ensure proper indexes on frequently queried columns
- **Constraints**: Use appropriate foreign key constraints
- **Soft Deletes**: Consider soft deletes for audit trails
- **Batch Operations**: Use transactions for multiple writes

### Money Calculations

- **Integer Math**: Use `bcdiv()`, `bcmul()` for precision if needed
- **Currency Consistency**: Validate all Money objects use same currency
- **Calculation Caching**: Cache expensive tax calculations
- **Precision**: Store amounts in cents for precision

## 🔄 Version Compatibility

### Laravel Version Support

- **Laravel 11**: Full support with all features
- **Laravel 12**: Full support (primary target)
- **PHP 8.3+**: Required minimum version
- **PHP 8.4+**: Recommended for performance

### Dependency Management

- **Money PHP**: Version 4.0+ with proper currency handling
- **Laravel Tools**: Spatie package tools for development
- **Testbench**: Orchestra for package testing
- **Pest**: Modern PHP testing framework

## 📝 Documentation Patterns

### README Sections

```markdown
# FlexCart

## Installation
composer require shamarkellman/flex-cart

## Usage
[Code examples]

## Configuration
[Environment variables and config options]

## Events
[Event system documentation]

## Testing
composer test

## Contributing
[Development setup and guidelines]
```

### Code Documentation

```php
/**
 * Brief description of class/method purpose
 *
 * @template TModel of Model
 * @param Type $param Description with constraints
 * @return ReturnType Description
 * @throws ExceptionType Conditions when exception is thrown
 *
 * @example
 * $result = FlexCart::addItem($product, 2);
 * @see RelatedClass::relatedMethod()
 */
```

## 🔍 Development Workflow

### Before Making Changes

1. **Run Tests**: `composer test` to ensure current functionality
2. **Static Analysis**: `composer analyse` to check code quality
3. **Review Dependencies**: Check compatibility with current Laravel version
4. **Documentation**: Update relevant README sections and PHPDocs

### After Making Changes

1. **Run Specific Tests**: Focus on changed functionality
2. **Integration Testing**: Test across different storage backends
3. **Event Testing**: Verify event dispatching works correctly
4. **Performance Testing**: Check for regressions with large carts
5. **Documentation Updates**: Update examples and API docs

### Branch Management

- **feature/**: For new features and enhancements
- **bugfix/**: For bug fixes and patches
- **refactor/**: For code restructuring without functionality changes
- Use descriptive branch names and commit messages

## 🚨 Common Pitfalls to Avoid

### Event System Pitfalls

❌ **Don't** modify event data after dispatching
❌ **Don't** dispatch events inside database transactions
❌ **Don't** create circular event dependencies
❌ **Do** include large objects in event payloads

### Database Pitfalls

❌ **Don't** use N+1 queries in loops
❌ **Don't** forget proper indexes on foreign keys
❌ **Don't** mix currency types in calculations
❌ **Don't** perform calculations in controllers

### Error Handling Pitfalls

❌ **Don't** catch generic `Exception` instead of specific types
❌ **Don't** expose sensitive information in error messages
❌ **Don't** ignore currency validation
❌ **Do** log raw exception traces in production

## 🔧 Tool Integration

### IDE Integration

- **PHPStorm**: Enable Laravel plugin and code style inspection
- **VS Code**: Enable PHP Intelephense and Laravel extension
- **Code Formatting**: Configure Pint to run on save
- **Debugging**: Use Xdebug with proper breakpoints

### External Tools

- **Git Hooks**: Configure pre-commit hooks for linting and testing
- **CI/CD**: GitHub Actions for automated testing
- **Package Management**: Use Composer for dependency management
- **Quality Gates**: Ensure tests pass before merging

---

## 📞 Getting Help

When working with FlexCart:

1. **Check README**: Look for specific examples in the documentation
2. **Review Tests**: Examine existing test patterns for similar functionality
3. **Study Code**: Understand existing patterns before making changes
4. **Ask Questions**: Use GitHub discussions for clarification
5. **Debug Systematically**: Use test-driven development approaches

This package follows Laravel best practices and provides comprehensive tools for shopping cart functionality. All code is strictly typed, well-tested, and production-ready.