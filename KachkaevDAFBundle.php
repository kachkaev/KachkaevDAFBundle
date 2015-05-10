<?php

namespace Kachkaev\DAFBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Kachkaev\DAFBundle\DependencyInjection\Compiler\ComponentAttributeUpdaterPass;

class KachkaevDAFBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ComponentAttributeUpdaterPass());
    }
}