<?php
declare(strict_types=1);

namespace EHDev\GDPRComplianceBundle;

use EHDev\GDPRComplianceBundle\DependencyInjection\EHDevGDPRComplianceExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EHDevGDPRComplianceBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new EHDevGDPRComplianceExtension();
    }
}
