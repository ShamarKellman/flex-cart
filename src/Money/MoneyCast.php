<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Money;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;

/**
 * @template TModel of Model
 */
class MoneyCast implements CastsAttributes
{
    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        $currency = new Currency(config('flex-cart.default_currency', 'USD'));

        if (is_array($value)) {
            return new Money($value['amount'], new Currency($value['currency']));
        }

        return new Money((int) $value, $currency);
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value instanceof Money) {
            return [
                $key => $value->getAmount(),
            ];
        }

        if (is_numeric($value)) {
            return [
                $key => (int) $value,
            ];
        }

        if (is_array($value) && isset($value['amount'])) {
            return [
                $key => (string) $value['amount'],
            ];
        }

        throw new InvalidArgumentException('Value must be an instance of Money\Money or numeric');
    }
}
