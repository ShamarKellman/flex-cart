<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Money;

use Money\Calculator\BcMathCalculator;
use Money\Money;

class Calculator
{
    private static ?BcMathCalculator $calculator = null;

    public static function add(Money $first, Money ...$collection): Money
    {
        return $first->add(...$collection);
    }

    public static function subtract(Money $first, Money ...$collection): Money
    {
        return $first->subtract(...$collection);
    }

    public static function multiply(Money $money, $multiplier): Money
    {
        return $money->multiply((string) $multiplier);
    }

    public static function divide(Money $money, $divisor): Money
    {
        return $money->divide((string) $divisor);
    }

    public static function percentage(Money $money, $percentage): Money
    {
        $calculator = self::getCalculator();
        $multiplier = $calculator::compare('0', (string) $percentage) === 0 ? '0' : $calculator::divide((string) $percentage, '100');

        return $money->multiply($multiplier);
    }

    public static function getTaxInclusiveAmount(Money $total, float $taxRate): array
    {
        if ($taxRate <= 0) {
            return [
                'net' => $total,
                'tax' => new Money(0, $total->getCurrency()),
                'rate' => $taxRate,
            ];
        }

        $calculator = self::getCalculator();
        $rate = (string) (1 + ($taxRate / 100));

        $netAmount = $calculator::divide($total->getAmount(), $rate);

        // Round to nearest integer (Money amount is in cents/smallest unit)
        $roundedNet = round((float) $netAmount);

        $net = new Money(
            (string) $roundedNet,
            $total->getCurrency()
        );

        $tax = $total->subtract($net);

        return [
            'net' => $net,
            'tax' => $tax,
            'rate' => $taxRate,
        ];
    }

    private static function getCalculator(): BcMathCalculator
    {
        if (self::$calculator === null) {
            self::$calculator = new BcMathCalculator;
        }

        return self::$calculator;
    }
}
