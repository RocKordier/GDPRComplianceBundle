<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle\Async\Producer;

use Doctrine\Common\Persistence\ObjectManager;
use EHDev\GDPRComplianceBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class DeleteEntityProducer
{
    private $messageProducer;
    private $manager;

    public function __construct(MessageProducerInterface $messageProducer, ObjectManager $manager)
    {
        $this->messageProducer = $messageProducer;
        $this->manager = $manager;
    }

    public function deleteObject($entity): void
    {
        if(!is_object($entity)) {
            return ;
        }

        $meta = $this->manager->getClassMetadata(get_class($entity));
        $identifier = $meta->getIdentifierValues($entity);

        if(is_array($identifier) && 1 === count($identifier)) {
            $this->produce(array_pop($identifier), get_class($entity));
        }
    }

    public function deletePlain(int $id, string $class): void
    {
        $this->produce($id, $class);
    }

    private function produce(int $id, string $class): void
    {
        $this->messageProducer->send(Topics::DELETE_ENTITY_PROCESSOR, ['id' => $id, 'class' => $class]);
    }
}
