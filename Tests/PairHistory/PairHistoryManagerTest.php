<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\Tests\PairHistory;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tbbc\MoneyBundle\MoneyException;
use Tbbc\MoneyBundle\Pair\SaveRatioEvent;
use Tbbc\MoneyBundle\PairHistory\PairHistoryManager;
use Tbbc\MoneyBundle\Tests\DatabaseTrait;

class PairHistoryManagerTest extends KernelTestCase
{
    use DatabaseTrait;

    protected PairHistoryManager $pairHistoryManager;
    protected ObjectRepository $ratioHistoryRepo;
    private ?ObjectManager $em;

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->pairHistoryManager = new PairHistoryManager(
            $this->em,
            'EUR'
        );
        $this->ratioHistoryRepo = $this->em->getRepository('Tbbc\MoneyBundle\Entity\RatioHistory');
        $this->createDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->dropDatabase();
        $this->em->close();
        $this->em = null;
    }

    public function testSaveRatioHistory(): void
    {
        $event = new SaveRatioEvent('EUR', 'USD', 1.25, new \DateTime('2012-07-08 12:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $ratioHistoryList = $this->ratioHistoryRepo->findAll();
        $this->assertSame(1, count($ratioHistoryList));

        $event = new SaveRatioEvent('EUR', 'USD', 1.50, new \DateTime('2012-07-08 13:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $ratioHistoryList = $this->ratioHistoryRepo->findAll();
        $this->assertSame(2, count($ratioHistoryList));
    }

    public function testGetRatioList(): void
    {
        $event = new SaveRatioEvent('EUR', 'USD', 1.25, new \DateTime('2012-07-08 12:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $event = new SaveRatioEvent('EUR', 'USD', 1.50, new \DateTime('2012-07-08 13:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $event = new SaveRatioEvent('EUR', 'USD', 1.75, new \DateTime('2012-07-08 14:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);

        $ratioList = $this->pairHistoryManager->getRatioHistory('USD', null, null);
        $this->assertSame(3, count($ratioList));
        $this->assertSame(1.25, $ratioList[0]['ratio']);
        $this->assertSame(1.50, $ratioList[1]['ratio']);
        $this->assertSame(1.75, $ratioList[2]['ratio']);
        $this->assertSame('2012-07-08 12:00:00', $ratioList[0]['savedAt']->format('Y-m-d H:i:s'));
        $this->assertSame('2012-07-08 13:00:00', $ratioList[1]['savedAt']->format('Y-m-d H:i:s'));
        $this->assertSame('2012-07-08 14:00:00', $ratioList[2]['savedAt']->format('Y-m-d H:i:s'));

        $ratioList = $this->pairHistoryManager->getRatioHistory('USD', new \DateTime('2012-07-08 12:30:00'), null);
        $this->assertSame(2, count($ratioList));
        $ratioList = $this->pairHistoryManager->getRatioHistory('USD', new \DateTime('2012-07-08 12:30:00'), new \DateTime('2012-07-08 13:30:00'));
        $this->assertSame(1, count($ratioList));
    }

    public function testGetRatio(): void
    {
        $event = new SaveRatioEvent('EUR', 'USD', 1.25, new \DateTime('2012-07-08 12:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $event = new SaveRatioEvent('EUR', 'USD', 1.50, new \DateTime('2012-07-08 13:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $event = new SaveRatioEvent('EUR', 'USD', 1.75, new \DateTime('2012-07-08 14:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);

        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2012-07-08 12:30:00'));
        $this->assertSame(1.25, $ratio);
        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2012-07-08 13:30:00'));
        $this->assertSame(1.50, $ratio);
        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2012-07-10 12:30:00'));
        $this->assertSame(1.75, $ratio);
        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2011-07-10 12:30:00'));
        $this->assertNull($ratio);

        $ratio = $this->pairHistoryManager->getRatioAtDate('EUR', new \DateTime('2011-07-10 12:30:00'));
        $this->assertSame(1.0, $ratio);
        $this->assertTrue(is_float($ratio));
    }

    public function testGetRatioException(): void
    {
        $event = new SaveRatioEvent('EUR', 'USD', 1.25, new \DateTime('2012-07-08 12:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $event = new SaveRatioEvent('CAD', 'USD', 1.50, new \DateTime('2012-07-08 13:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);
        $event = new SaveRatioEvent('EUR', 'USD', 1.75, new \DateTime('2012-07-08 14:00:00'));
        $this->pairHistoryManager->listenSaveRatioEvent($event);

        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2012-07-08 12:30:00'));
        $this->assertSame(1.25, $ratio);
        try {
            $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2012-07-08 13:30:00'));
            $this->fail('should throw an exception du to reference currency code');
        } catch (MoneyException $e) {
            $this->assertTrue(true);
        }
        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2012-07-10 12:30:00'));
        $this->assertSame(1.75, $ratio);
        $ratio = $this->pairHistoryManager->getRatioAtDate('USD', new \DateTime('2011-07-10 12:30:00'));
        $this->assertNull($ratio);
    }
}
