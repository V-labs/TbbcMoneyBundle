<?php

namespace Tbbc\MoneyBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Tbbc\MoneyBundle\Entity\DoctrineCurrency;

class DoctrineCurrencyRepository extends EntityRepository
{
    /**
     * @return DoctrineCurrency
     * @throws NonUniqueResultException
     */
    public function getReferenceCurrency(): DoctrineCurrency
    {
        $qb = $this->createQueryBuilder('doctrine_currency');

        $qb->andWhere($qb->expr()->eq('doctrine_currency.reference', true));

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function add(DoctrineCurrency $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
