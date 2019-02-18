<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle\Async\Processor;

use EHDev\GDPRComplianceBundle\Async\Topics;
use EHDev\GDPRComplianceBundle\GDPR\DeleteEntity;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class DeleteEntityProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $deleteEntity;

    public function __construct(DeleteEntity $deleteEntity)
    {
        $this->deleteEntity = $deleteEntity;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $body = JSON::decode($message->getBody());
        } catch (\InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());

            return self::REJECT;
        }

        if (!isset($body['id']) || !isset($body['class'])) {
            $this->logger->warning(
                sprintf('[%s] Message could not processed', Topics::DELETE_ENTITY_PROCESSOR),
                $body
            );

            return self::REJECT;
        }

        $result = $this->deleteEntity->delete($body['id'], $body['class']);

        return $result ? self::ACK : self::REJECT;
    }

    public static function getSubscribedTopics()
    {
        return [Topics::DELETE_ENTITY_PROCESSOR];
    }
}
