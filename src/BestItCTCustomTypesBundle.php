<?php

namespace BestIt\CTCustomTypesBundle;

use BestIt\CTCustomTypesBundle\DependencyInjection\Compiler\CollectionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The bundle for custom types.
 * @author blange <lange@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @version $id$
 */
class BestItCTCustomTypesBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CollectionCompilerPass());
    }
}
