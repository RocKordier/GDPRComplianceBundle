<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle\GDPR;

use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class DeleteEntity implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->logger = new NullLogger();
    }

    public function delete(int $id, string $class): bool
    {
        if ($this->manager->getMetadataFactory()->isTransient($class)) {
            $this->logger->notice(sprintf('%s is not a managed by doctrine', $class));

            return false;
        }

        $repo = $this->manager->getRepository($class);
        $entity = $repo->find($id);

        if ($entity) {
            $this->manager->remove($entity);
            $this->manager->flush();

            return true;
        }

        $this->logger->notice(sprintf('%s with ID %s cannot be found', $class, $id));

        return false;
    }
}
