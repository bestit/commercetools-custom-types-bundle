<?php

namespace BestIt\CTCustomTypesBundle\DependencyInjection\Compiler;

use BestIt\CTCustomTypesBundle\Model\CustomTypeResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collect all custom types into collection
 * @author chowanski <michel.chowanski@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle\DependencyInjection\Compiler
 * @version $id$
 */
class CollectionCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('best_it.ct_custom_types.collection.custom_type_collection')) {
            $definition = $container->getDefinition('best_it.ct_custom_types.collection.custom_type_collection');

            /*
             * We don't want to set custom type keys twice (in custom type yml and in config yml)
             * So we detect all resource type id from our current custom type yml and set a key value pair
             * resource => type key
             * Eg. "order" =>   [
             *                      "bh-cart",
             *                      "bj-custom-cart",
             *                      "cart-nk"
             *                  ]
             */
            if ($customTypes = $container->getParameter('best_it_ct_custom_types.types')) {
                foreach ($customTypes as $name => $type) {
                    foreach ($type['resourceTypeIds'] as $resourceTypeId) {
                        $definition->addMethodCall('add', [$resourceTypeId, $name]);
                    }
                }
            }
        }
    }
}
