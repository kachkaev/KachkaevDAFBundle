<?php

namespace Kachkaev\DAFBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('daf');

        $rootNode
            ->children()
            ->scalarNode('data_dir')
                ->isRequired()
                ->cannotBeEmpty()
                ->end()
            ->scalarNode('dataset_backup_dir')
                ->isRequired()
                ->cannotBeEmpty()
                ->end()
            ->integerNode('default_chunk_size')
                ->defaultValue(100)
                ->end()
            ->arrayNode('query_templates_namespace_lookups')
                ->prototype('array')
                    ->children()
                        ->scalarNode('bundle')->end()
                        ->scalarNode('path')->end()
                    ->end()
                ->end()
            ->end() // query_templates_namespace_lookups
        ->end()
        ;

        return $treeBuilder;
    }
}
