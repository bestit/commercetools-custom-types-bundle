<?php

namespace BestIt\CTCustomTypesBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads the config for the bundle.
 * @author lange <lange@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @subpackage DependencyInjection
 * @version $id$
 */
class BestItCTCustomTypesExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setAlias('best_it_ct_custom_types.client', $config['commercetools_client_service']);
        $container->setParameter('best_it_ct_custom_types.types', $config['types'] ?? []);
        $container->setParameter('best_it_ct_custom_types.whitelist', $config['whitelist'] ?? []);

        $alias = $container->getAlias('best_it_ct_custom_types.client');
        $alias->setPublic(true);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
