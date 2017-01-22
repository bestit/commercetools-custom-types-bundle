<?php

namespace BestIt\CTCustomTypesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
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
     * Loads the bundle config.
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setAlias('best_it_ct_custom_types.client', $config['commercetools_client_service']);
        $container->setParameter('best_it_ct_custom_types.types', $config['types'] ?? []);
        $container->setParameter('best_it_ct_custom_types.whitelist', $config['whitelist'] ?? []);
    }
}
