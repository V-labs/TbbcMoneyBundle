<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\Pair;

use DateTime;
use DateTimeImmutable;
use Money\Converter;
use Money\Currencies;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\CurrencyPair;
use Money\Exchange;
use Money\Money;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tbbc\MoneyBundle\Entity\DoctrineCurrency;
use Tbbc\MoneyBundle\Manager\DoctrineCurrencyManager;
use Tbbc\MoneyBundle\MoneyException;
use Tbbc\MoneyBundle\Repository\DoctrineCurrencyRepository;
use Tbbc\MoneyBundle\TbbcMoneyEvents;
use Traversable;

/**
 * Class PairManager.
 *
 * @author Philippe Le Van.
 */
class PairManager implements PairManagerInterface, Exchange
{
    protected Currencies $currencies;
    protected array $ratioProviders;

    public function __construct(
        protected StorageInterface $storage,
        protected EventDispatcherInterface $dispatcher,
        protected DoctrineCurrencyManager $doctrineCurrencyManager,
        iterable $ratioProviders
    ) {
        $this->currencies = new ISOCurrencies();
        $this->ratioProviders = $ratioProviders instanceof Traversable ? iterator_to_array($ratioProviders) : $ratioProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(Money $amount, string $currencyCode): Money
    {
        $converter = new Converter($this->currencies, $this);

        return $converter->convert($amount, new Currency($currencyCode));
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency): CurrencyPair
    {
        $ratio = $this->getRelativeRatio($baseCurrency->getCode(), $counterCurrency->getCode());

        return new CurrencyPair($baseCurrency, $counterCurrency, (string) $ratio);
    }

    /**
     * {@inheritdoc}
     */
    public function saveRatio(string $currencyCode, float $ratio): void
    {
        $currency = new Currency($currencyCode);

        if ($ratio <= 0) {
            throw new MoneyException('ratio has to be strictly positive');
        }

        $ratioList = $this->storage->loadRatioList(true);
        $ratioList[$currency->getCode()] = $ratio;
        $ratioList[$this->getReferenceCurrencyCode()] = 1.0;
        $this->storage->saveRatioList($ratioList);

        $savedAt = new DateTime();
        $event = new SaveRatioEvent(
            $this->getReferenceCurrencyCode(),
            $currencyCode,
            $ratio,
            $savedAt
        );

        $this->dispatcher->dispatch($event, TbbcMoneyEvents::AFTER_RATIO_SAVE);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelativeRatio(string $referenceCurrencyCode, string $currencyCode): float
    {
        $currency = new Currency($currencyCode);
        $referenceCurrency = new Currency($referenceCurrencyCode);
        if ($currencyCode === $referenceCurrencyCode) {
            return 1.0;
        }

        $ratioList = $this->storage->loadRatioList();
        if (!array_key_exists($currency->getCode(), $ratioList)) {
            throw new MoneyException('unknown ratio for currency '.$currencyCode);
        }

        if (!array_key_exists($referenceCurrency->getCode(), $ratioList)) {
            throw new MoneyException('unknown ratio for currency '.$referenceCurrencyCode);
        }

        /** @var float $source */
        $source = $ratioList[$currency->getCode()];
        /** @var float $reference */
        $reference = $ratioList[$referenceCurrency->getCode()];

        return $source / $reference;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyCodeList(): array
    {
        return $this->doctrineCurrencyManager->getCurrencyCodeList();
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceCurrencyCode(): string
    {
        return $this->doctrineCurrencyManager->getReferenceCurrency()->getCurrencyCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getRatioList(): array
    {
        return $this->storage->loadRatioList();
    }

    /**
     * {@inheritdoc}
     */
    public function saveRatioListFromRatioProvider(): void
    {
        /** @var DoctrineCurrency $doctrineCurrency */
        foreach ($this->doctrineCurrencyManager->findAll() as $doctrineCurrency) {

            if ($doctrineCurrency->getCurrencyCode() != $this->getReferenceCurrencyCode()) {
                $doctrineCurrency->setUpdatedAt(new DateTimeImmutable());

                /** @var RatioProviderInterface $ratioProvider */
                $ratioProvider = $this->ratioProviders[$doctrineCurrency->getProvider()];

                $ratio = $ratioProvider->fetchRatio($this->getReferenceCurrencyCode(), $doctrineCurrency->getCurrencyCode());
                $this->saveRatio($doctrineCurrency->getCurrencyCode(), $ratio);
            }
        }
    }
}
