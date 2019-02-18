<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle\Tests\Async\Processor;

use Doctrine\DBAL\Statement;
use EHDev\GDPRComplianceBundle\Async\Processor\DeleteEntityProcessor;
use EHDev\GDPRComplianceBundle\Async\Topics;
use EHDev\GDPRComplianceBundle\GDPR\DeleteEntity;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class DeleteEntityProcessorTest extends TestCase
{
    private $message;
    private $session;
    private $deleteEntity;

    protected function setUp()
    {
        $this->deleteEntity = $this->prophesize(DeleteEntity::class);
        $this->message = $this->prophesize(MessageInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
    }

    public function testProcessInvalideJson()
    {
        $this->message->getBody()->willReturn('{"invalide"');

        $processor = new DeleteEntityProcessor($this->deleteEntity->reveal());
        $return = $processor->process($this->message->reveal(), $this->session->reveal());

        $this->assertEquals(MessageProcessorInterface::REJECT, $return);
    }

    /**
     * @dataProvider incompleteJsonProvider
     */
    public function testIncompleteJson($data)
    {
        $this->message->getBody()->willReturn($data);

        $processor = new DeleteEntityProcessor($this->deleteEntity->reveal());
        $return = $processor->process($this->message->reveal(), $this->session->reveal());

        $this->assertEquals(MessageProcessorInterface::REJECT, $return);
    }

    public function incompleteJsonProvider()
    {
        yield [ '{"id":3}' ];
        yield [ '{"class":"Class"}' ];
        yield [ '{"bla":3}' ];
    }

    /**
     * @dataProvider entityDeleteProvider
     */
    public function testEntityDelete($willReturn, $expected)
    {
        $this->message->getBody()->willReturn('{"id":3, "class": "class"}');
        $this->deleteEntity->delete()->shouldBeCalledOnce()->willReturn($willReturn)->withArguments([3, 'class']);

        $processor = new DeleteEntityProcessor($this->deleteEntity->reveal());
        $return = $processor->process($this->message->reveal(), $this->session->reveal());

        $this->assertEquals($expected, $return);
    }

    public function entityDeleteProvider()
    {
        yield [ true, MessageProcessorInterface::ACK ];
        yield [ false, MessageProcessorInterface::REJECT ];
    }

    public function testGetSubscribedTopics()
    {
        $processor = new DeleteEntityProcessor($this->deleteEntity->reveal());
        $topics = $processor->getSubscribedTopics();

        $this->assertEquals(1, count($topics));
        $this->assertTrue(
            Topics::DELETE_ENTITY_PROCESSOR === array_pop($topics)
        );
    }
}
