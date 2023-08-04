<?php

namespace Tbbc\MoneyBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Tbbc\MoneyBundle\Entity\DoctrineCurrency;
use Tbbc\MoneyBundle\Repository\DoctrineCurrencyRepositoryInterface;

class DoctrineCurrencyManager
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected string $currencyClass
    ){

    }

    private function getClass()
    {
        if (str_contains($this->currencyClass, ':')) {
            $metadata = $this->entityManager->getClassMetadata($this->currencyClass);
            $this->currencyClass = $metadata->getName();
        }

        return $this->currencyClass;
    }

    /**
     * @return DoctrineCurrencyRepositoryInterface
     */
    protected function getRepository(): DoctrineCurrencyRepositoryInterface
    {
        /** @var DoctrineCurrencyRepositoryInterface $repository */
        $repository = $this->entityManager->getRepository($this->getClass());

        return $repository;
    }

    /**
     * @return DoctrineCurrency
     * @throws NonUniqueResultException
     */
    public function getReferenceCurrency(): DoctrineCurrency
    {
        return $this->getRepository()->getReferenceCurrency();
    }

    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    public function getCurrencyCodeList()
    {
        return array_map(function (DoctrineCurrency $doctrineCurrency) {
            return $doctrineCurrency->getCurrencyCode();
        }, $this->getRepository()->findAll());
    }
}
