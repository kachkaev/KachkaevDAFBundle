<?php

namespace Kachkaev\DAFBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Creates a collection of all tagged daf.property_updater services
 *
 */
class PropertyUpdaterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('daf.property_updaters')) {
            return;
        }

        $definition = $container->getDefinition('daf.property_updaters');

        foreach ($container->findTaggedServiceIds('daf.property_updater') as $id => $attributes) {
            $definition->addMethodCall('add', array(new Reference($id)));
        };
    }
}
