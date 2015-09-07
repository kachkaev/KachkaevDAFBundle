<?php

namespace Kachkaev\DAFBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class KachkaevDAFExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'daf';
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);


        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');

        $container->setParameter('daf.data_dir', $config['data_dir']);
        $container->setParameter('daf.dataset_backup_dir', $config['dataset_backup_dir']);
        $container->setParameter('daf.query_templates_namespace_lookups', $config['query_templates_namespace_lookups']);

        $container->setParameter('daf.default_chunk_size', $config['default_chunk_size']);
    }
}
