<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle\Tests\Async\Producer;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use EHDev\GDPRComplianceBundle\GDPR\DeleteEntity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DeleteEntityTest extends TestCase
{
    private $manager;

    protected function setUp()
    {
        $this->manager = $this->prophesize(ObjectManager::class);
    }

    public function testDeleteNotEntity()
    {
        $meta = $this->prophesize(ClassMetadataFactory::class);
        $meta->isTransient(Argument::any())->willReturn(true);
        $this->manager->getMetadataFactory()->willReturn($meta->reveal());

        $delete = new DeleteEntity($this->manager->reveal());
        $return = $delete->delete(1, 'class');

        $this->manager->remove()->shouldNotBeCalled();
        $this->manager->flush()->shouldNotBeCalled();

        $this->assertFalse($return);
    }

    public function testEntityNotFound()
    {
        $meta = $this->prophesize(ClassMetadataFactory::class);
        $meta->isTransient(Argument::any())->willReturn(false);
        $this->manager->getMetadataFactory()->willReturn($meta->reveal());

        $repo = $this->prophesize(EntityRepository::class);
        $repo->find(Argument::any())->willReturn(null);
        $this->manager->getRepository(Argument::any())->willReturn($repo->reveal());

        $this->manager->remove()->shouldNotBeCalled();
        $this->manager->flush()->shouldNotBeCalled();

        $delete = new DeleteEntity($this->manager->reveal());
        $return = $delete->delete(1, 'class');

        $this->assertFalse($return);
    }

    public function testEntityFound()
    {
        $meta = $this->prophesize(ClassMetadataFactory::class);
        $meta->isTransient(Argument::any())->willReturn(false);
        $this->manager->getMetadataFactory()->willReturn($meta->reveal());

        $repo = $this->prophesize(EntityRepository::class);
        $repo->find(Argument::any())->willReturn(new \stdClass());
        $this->manager->getRepository(Argument::any())->willReturn($repo->reveal());

        $this->manager->remove(Argument::any())->shouldBeCalledOnce();
        $this->manager->flush()->shouldBeCalledOnce();

        $delete = new DeleteEntity($this->manager->reveal());
        $return = $delete->delete(1, 'class');

        $this->assertTrue($return);
    }
}
