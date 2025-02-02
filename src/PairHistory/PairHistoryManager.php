<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\PairHistory;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Tbbc\MoneyBundle\Entity\DoctrineCurrency;
use Tbbc\MoneyBundle\Entity\RatioHistory;
use Tbbc\MoneyBundle\Manager\DoctrineCurrencyManager;
use Tbbc\MoneyBundle\MoneyException;
use Tbbc\MoneyBundle\Pair\SaveRatioEvent;
use Tbbc\MoneyBundle\Repository\DoctrineCurrencyRepository;

/**
 * Class PairHistoryManager.
 */
class PairHistoryManager implements PairHistoryManagerInterface
{
    public function __construct(protected EntityManagerInterface $em, protected DoctrineCurrencyManager $doctrineCurrencyManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRatioAtDate(string $currencyCode, DateTimeInterface $savedAt): ?float
    {
        $referenceCurrencyCode = $this->doctrineCurrencyManager->getReferenceCurrency()->getCurrencyCode();

        if ($currencyCode == $referenceCurrencyCode) {
            return 1.0;
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('rh')
            ->from(\Tbbc\MoneyBundle\Entity\RatioHistory::class, 'rh')
            ->where('rh.currencyCode = :currencyCode')
            ->orderBy('rh.savedAt', 'DESC')
            ->andWhere('rh.savedAt <= :historyDate')
            ->setParameter('historyDate', $savedAt, Types::DATETIME_MUTABLE)
            ->setParameter('currencyCode', $currencyCode)
            ->setMaxResults(1)
        ;
        $query = $qb->getQuery();
        try {
            /** @var RatioHistory $ratioHistory */
            $ratioHistory = $query->getSingleResult();
        } catch (NoResultException) {
            return null;
        }

        if ($ratioHistory->getReferenceCurrencyCode() !== $referenceCurrencyCode) {
            throw new MoneyException('Reference currency code changed in history of currency ratio');
        }

        return $ratioHistory->getRatio();
    }

    /**
     * {@inheritdoc}
     */
    public function getRatioHistory(string $currencyCode, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $referenceCurrencyCode = $this->doctrineCurrencyManager->getReferenceCurrency()->getCurrencyCode();

        $qb = $this->em->createQueryBuilder();
        $qb->select('rh')
            ->from(\Tbbc\MoneyBundle\Entity\RatioHistory::class, 'rh')
            ->where('rh.currencyCode = :currencyCode')
            ->andWhere('rh.referenceCurrencyCode = :referenceCurrencyCode')
            ->orderBy('rh.savedAt', 'ASC')
            ->setParameter('currencyCode', $currencyCode)
            ->setParameter('referenceCurrencyCode', $referenceCurrencyCode)
        ;
        if ($startDate instanceof DateTime) {
            $qb->andWhere('rh.savedAt >= :startDate')
                ->setParameter('startDate', $startDate, Types::DATETIME_MUTABLE);
        }
        if ($endDate instanceof DateTime) {
            $qb->andWhere('rh.savedAt <= :endDate')
                ->setParameter('endDate', $endDate, Types::DATETIME_MUTABLE);
        }
        $query = $qb->getQuery();
        /** @var RatioHistory[] $resultList */
        $resultList = $query->getResult();
        $res = [];

        foreach ($resultList as $ratioHistory) {
            $res[] = [
                'ratio' => $ratioHistory->getRatio(),
                'savedAt' => $ratioHistory->getSavedAt(),
            ];
        }

        return $res;
    }

    public function listenSaveRatioEvent(SaveRatioEvent $event): void
    {
        $ratioHistory = new RatioHistory();
        $ratioHistory->setReferenceCurrencyCode($event->getReferenceCurrencyCode());
        $ratioHistory->setCurrencyCode($event->getCurrencyCode());
        $ratioHistory->setRatio($event->getRatio());
        $ratioHistory->setSavedAt($event->getSavedAt());
        $this->em->persist($ratioHistory);
        $this->em->flush();
    }
}
