<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle\Tests\Async\Producer;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Mapping\ClassMetadata;
use EHDev\GDPRComplianceBundle\Async\Processor\DeleteEntityProcessor;
use EHDev\GDPRComplianceBundle\Async\Producer\DeleteEntityProducer;
use EHDev\GDPRComplianceBundle\Async\Topics;
use EHDev\GDPRComplianceBundle\GDPR\DeleteEntity;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class DeleteEntityProcessorTest extends TestCase
{
    private $messageProducer;
    private $manager;

    protected function setUp()
    {
        $this->messageProducer = $this->prophesize(MessageProducerInterface::class);
        $this->manager = $this->prophesize(ObjectManager::class);
    }

    public function testDeletePlain()
    {
        $this->messageProducer->send(Argument::any(), Argument::any())->shouldBeCalledOnce();

        $producer = new DeleteEntityProducer($this->messageProducer->reveal(), $this->manager->reveal());
        $producer->deletePlain(1, 'class');
    }

    public function testDeleteObject()
    {
        $classMeta = $this->prophesize(ClassMetadata::class);
        $classMeta->getIdentifierValues(Argument::any())->willReturn([1]);

        $this->manager->getClassMetadata(Argument::any())->willReturn($classMeta->reveal());

        $this->messageProducer->send(Argument::any(), Argument::any())->shouldBeCalledOnce();
        $producer = new DeleteEntityProducer($this->messageProducer->reveal(), $this->manager->reveal());

        $producer->deleteObject(new \stdClass());
    }

    /**
     * @dataProvider deleteFailedProvider
     */
    public function testDeleteObjectfailed($failedEntity)
    {
        $this->messageProducer->send()->shouldNotBeCalled();

        $producer = new DeleteEntityProducer($this->messageProducer->reveal(), $this->manager->reveal());
        $producer->deleteObject($failedEntity);
    }

    public function deleteFailedProvider()
    {
        yield [ 'string' ];
        yield [ 1 ];
        yield [ 1.1 ];
        yield [ null ];
        yield [ false ];
        yield [ true ];
    }
}
