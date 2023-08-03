<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\Entity;

class DoctrineCurrency
{
    protected mixed $id = null;
    protected string $currencyCode = '';
    protected bool $reference = false;
    protected string $provider = '';

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): DoctrineCurrency
    {
        $this->id = $id;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): DoctrineCurrency
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function isReference(): bool
    {
        return $this->reference;
    }

    public function setReference(bool $reference): DoctrineCurrency
    {
        $this->reference = $reference;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): DoctrineCurrency
    {
        $this->provider = $provider;

        return $this;
    }
}
