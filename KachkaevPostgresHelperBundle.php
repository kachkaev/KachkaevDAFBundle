<?php

namespace Kachkaev\PostgresHelperBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Kachkaev\PostgresHelperBundle\DependencyInjection\Compiler\ComponentAttributeUpdaterPass;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class KachkaevPostgresHelperBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ComponentAttributeUpdaterPass());
    }
}