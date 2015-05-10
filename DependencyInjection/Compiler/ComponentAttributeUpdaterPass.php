<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kachkaev\DAFBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Creates a collection of all tagged daf.component_attribute_updater services
 *
 */
class ComponentAttributeUpdaterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('daf.component_attribute_updaters')) {
            return;
        }
        
        $definition = $container->getDefinition('daf.component_attribute_updaters');
        
        foreach ($container->findTaggedServiceIds('daf.component_attribute_updater') as $id => $attributes) {
            $definition->addMethodCall('add', array(new Reference($id)));
        };
    }
}
