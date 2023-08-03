<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\Money;

use Money\Currency;
use Money\Money;
use Tbbc\MoneyBundle\Repository\DoctrineCurrencyRepository;

/**
 * Class MoneyManager.
 *
 * @author levan
 */
class MoneyManager implements MoneyManagerInterface
{
    /**
     * MoneyManager constructor.
     */
    public function __construct(protected DoctrineCurrencyRepository $doctrineCurrencyRepository, protected int $decimals = 2)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createMoneyFromFloat(float $floatAmount, ?string $currencyCode = null): Money
    {
        if (is_null($currencyCode)) {
            $currencyCode = $this->doctrineCurrencyRepository->getReferenceCurrency()->getCurrencyCode();
        }
        $currency = new Currency($currencyCode);
        $amountAsInt = $floatAmount * 10 ** $this->decimals;
        $amountAsInt = round($amountAsInt);
        $amountAsInt = intval($amountAsInt);

        return new Money($amountAsInt, $currency);
    }
}
