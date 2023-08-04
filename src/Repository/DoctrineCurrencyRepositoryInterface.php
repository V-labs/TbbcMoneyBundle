<?php

namespace Tbbc\MoneyBundle\Repository;

use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\NonUniqueResultException;
use Tbbc\MoneyBundle\Entity\DoctrineCurrency;

interface DoctrineCurrencyRepositoryInterface extends ObjectRepository
{
    /**
     * @return DoctrineCurrency
     * @throws NonUniqueResultException
     */
    public function getReferenceCurrency(): DoctrineCurrency;
}
