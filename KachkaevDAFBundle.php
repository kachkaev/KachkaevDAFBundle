<?php

namespace Kachkaev\DAFBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Kachkaev\DAFBundle\DependencyInjection\Compiler\ComponentAttributeUpdaterPass;
use Kachkaev\DAFBundle\DependencyInjection\KachkaevDAFExtension;

class KachkaevDAFBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ComponentAttributeUpdaterPass());
    }

    public function getContainerExtension()
    {
        return new KachkaevDAFExtension();
    }
}