<?php

namespace Kachkaev\DatasetAbstractionBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Kachkaev\DatasetAbstractionBundle\DependencyInjection\Compiler\ComponentAttributeUpdaterPass;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class KachkaevDatasetAbstractionBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ComponentAttributeUpdaterPass());
    }
}