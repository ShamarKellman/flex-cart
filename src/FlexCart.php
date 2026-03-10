<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\BuyableInterface;
use ShamarKellman\FlexCart\Contracts\CartInterface;
use ShamarKellman\FlexCart\Contracts\CartItemInterface;
use ShamarKellman\FlexCart\Contracts\CartStorageInterface;
use ShamarKellman\FlexCart\Coupon\CouponManager;
use ShamarKellman\FlexCart\Events\CartCleared;
use ShamarKellman\FlexCart\Exceptions\CurrencyMismatchException;
use ShamarKellman\FlexCart\Exceptions\ProductNotBuyableException;
use ShamarKellman\FlexCart\Models\Cart;
use ShamarKellman\FlexCart\Models\CartItem;
use ShamarKellman\FlexCart\Models\Coupon;
use ShamarKellman\FlexCart\Models\ShippingDetail;
use ShamarKellman\FlexCart\Money\Calculator;
use ShamarKellman\FlexCart\Traits\HasCoupons;
use ShamarKellman\FlexCart\Traits\HasItems;
use ShamarKellman\FlexCart\Traits\HasShipping;

use function event;

/**
 * @template TModel of Model
 */
class FlexCart implements CartInterface
{
    use HasCoupons;
    use HasItems;
    use HasShipping;

    protected CartStorageInterface $storage;

    protected string $sessionKey;

    protected ?Cart $cart = null;

    /**
     * @var Collection<CartItemInterface>
     */
    protected Collection $items;

    protected ?ShippingDetail $shippingDetail = null;

    protected Money $shippingCost;

    protected float $taxRate;

    protected CouponManager $couponManager;

    public function __construct(CartStorageInterface $storage)
    {
        $this->storage = $storage;
        $this->sessionKey = session()->getId();
        $this->taxRate = config()->float('flex-cart.tax_rate', 0.0);
        $this->shippingCost = new Money(0, new Currency(config('flex-cart.default_currency', 'USD')));
        $this->items = collect();
        $this->couponManager = new CouponManager;
        $this->load();
    }

    /**
     * Clear all items from the cart
     */
    public function clear(): void
    {
        $clearedItems = $this->items->map(fn ($item) => $item);

        $this->items = collect();
        $this->shippingDetail = null;
        $this->shippingCost = new Money(0, $this->getCurrency());
        $this->clearCoupons();
        $this->save();

        // Dispatch cart cleared event
        event(new CartCleared($clearedItems));
    }

    /**
     * Calculate the total amount including tax, shipping, and discounts
     */
    public function total(): Money
    {
        $subtotal = $this->subtotal();
        $generalDiscount = $this->generalDiscount();
        $shippingDiscount = $this->shippingDiscount();

        $shippingCostAfterDiscount = $this->shippingCost->subtract($shippingDiscount);
        if ($shippingCostAfterDiscount->isNegative()) {
            $shippingCostAfterDiscount = new Money(0, $this->getCurrency());
        }

        return $subtotal->add($shippingCostAfterDiscount)->subtract($generalDiscount);
    }

    /**
     * Calculate the subtotal (sum of all item totals before tax and discounts)
     */
    public function subtotal(): Money
    {
        $total = new Money(0, $this->getCurrency());

        foreach ($this->items as $item) {
            $total = Calculator::add($total, $item->total_price);
        }

        return $total;
    }

    /**
     * Calculate the net subtotal (item totals minus tax)
     */
    public function subtotalNet(): Money
    {
        $total = new Money(0, $this->getCurrency());

        foreach ($this->items as $item) {
            $total = Calculator::add($total, $item->total_price->subtract($item->tax_amount));
        }

        return $total;
    }

    /**
     * Calculate the total tax amount
     */
    public function tax(): Money
    {
        if ($this->taxRate <= 0) {
            return new Money(0, $this->getCurrency());
        }

        $totalTax = new Money(0, $this->getCurrency());

        foreach ($this->items as $item) {
            $totalTax = Calculator::add($totalTax, $item->tax_amount);
        }

        return $totalTax;
    }

    /**
     * Get the total number of items in the cart
     */
    public function count(): int
    {
        /** @var int $count */
        $count = $this->items->reduce(fn (int $carry, CartItemInterface $item): int => $carry + $item->getQuantity(), 0);

        return $count;
    }

    /**
     * Check if the cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Get the cart model instance
     */
    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    /**
     * Load cart data from storage
     */
    protected function load(): void
    {
        /**
         * @var array{cart?: array, items?: array, shipping?: array, coupons?: array} $data
         */
        $data = $this->storage->get($this->sessionKey);

        if ($data) {
            $this->cart = new Cart($data['cart'] ?? []);
            if (isset($data['cart']['id'])) {
                $this->cart->id = $data['cart']['id'];
                $this->cart->exists = true;
            }

            $this->items = collect($data['items'] ?? [])->map(fn (mixed $item) => new CartItem($item));

            if (isset($data['shipping'])) {
                $this->shippingDetail = new ShippingDetail($data['shipping']);
            }

            if (isset($data['cart']['shipping_cost'])) {
                $shippingCost = $data['cart']['shipping_cost'];
                if ($shippingCost instanceof Money) {
                    $this->shippingCost = $shippingCost;
                } elseif (is_array($shippingCost)) {
                    $this->shippingCost = new Money((string) $shippingCost['amount'], new Currency($shippingCost['currency']));
                } elseif (is_numeric($shippingCost)) {
                    $this->shippingCost = new Money((string) $shippingCost, $this->getCurrency());
                }
            }

            if (isset($data['coupons'])) {
                foreach ($data['coupons'] as $couponData) {
                    $coupon = new Coupon($couponData);
                    $this->couponManager->addCoupon($coupon);
                }
            }
        }
    }

    /**
     * Save cart data to storage
     */
    protected function save(): void
    {
        $this->cart ??= new Cart;

        $data = [
            'cart' => $this->prepareCartData(),
            'items' => $this->prepareItemsData(),
            'shipping' => $this->shippingDetail?->toArray(),
            'coupons' => $this->prepareCouponsData(),
        ];

        $this->storage->put($this->sessionKey, $data);
    }

    /**
     * Prepare cart data for storage
     *
     * @return array<string, mixed>
     */
    protected function prepareCartData(): array
    {
        return [
            'session_id' => $this->sessionKey,
            'user_id' => Auth::id(),
            'currency' => config('flex-cart.default_currency', 'USD'),
            'subtotal' => (int) $this->subtotal()->getAmount(),
            'tax_amount' => (int) $this->tax()->getAmount(),
            'shipping_cost' => (int) $this->shippingCost->getAmount(),
            'total' => (int) $this->total()->getAmount(),
        ];
    }

    /**
     * Get the price from a buyable model
     *
     * @param  TModel  $buyable
     *
     * @throws ProductNotBuyableException
     */
    protected function getBuyablePrice(Model $buyable): Money
    {
        if (! $buyable instanceof BuyableInterface) {
            throw ProductNotBuyableException::missingPrice($buyable);
        }

        return $buyable->getPrice();
    }

    /**
     * Get the current currency
     */
    protected function getCurrency(): Currency
    {
        return new Currency(config()->string('flex-cart.default_currency', 'USD'));
    }

    /**
     * Validate that a Money object matches the expected currency
     *
     * @throws CurrencyMismatchException
     */
    protected function validateCurrency(Money $money, string $context = 'operation'): void
    {
        $expectedCurrency = $this->getCurrency()->getCode();
        $actualCurrency = $money->getCurrency()->getCode();

        if ($expectedCurrency !== $actualCurrency) {
            throw CurrencyMismatchException::create($expectedCurrency, $actualCurrency, $context);
        }
    }
}
