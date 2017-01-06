<?php

namespace BestIt\CTCustomTypesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for this bundle.
 * @author blange <lange@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @subpackage DependencyInjection
 * @version $id$
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Parses the config.
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('best_it_ct_custom_types')
            ->children()
                ->append($this->getTypesNode())
                ->scalarNode('commercetools_client_service')->isRequired()->end()
            ->end();

        return $builder;
    }

    /**
     * Adds the types to the config.
     * @return ArrayNodeDefinition
     */
    protected function getTypesNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder())->root('types');

        $node
            ->info(
                'Add the types mainly documented under: ' .
                    '<https://dev.commercetools.com/http-api-projects-types.html>'
            )
            ->normalizeKeys(false)
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('key')
            ->prototype('array')
                ->children()
                    ->arrayNode('name')
                        ->isRequired()
                        ->useAttributeAsKey('lang')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('description')
                        ->isRequired()
                        ->useAttributeAsKey('lang')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('resourceTypeIds')
                        ->info(
                            'https://dev.commercetools.com/http-api-projects-custom-fields' .
                            '.html#customizable-resources'
                        )
                        ->isRequired()
                        ->prototype('scalar')->isRequired()->end()
                    ->end()
                    // TODO: We still need to support localizedenum, sets, reference, etc.
                    ->arrayNode('fieldDefinitions')
                        ->info('http://dev.commercetools.com/http-api-projects-types.html#fielddefinition')
                        ->isRequired()
                        ->normalizeKeys(false)
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->arrayNode('type')
                                    ->isRequired()
                                    ->children()
                                        ->enumNode('name')
                                            ->isRequired()
                                            ->values([
                                                'Boolean',
                                                'String',
                                                'LocalizedString',
                                                'Enum',
                                                'Number',
                                                'Money',
                                                'Date',
                                                'Time',
                                                'DateTime'
                                            ])
                                        ->end()
                                        ->arrayNode('values')
                                            ->useAttributeAsKey('key')
                                            ->prototype('scalar')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->booleanNode('required')->isRequired()->defaultValue(false)->end()
                                ->enumNode('inputHint')->isRequired()->values(['MultiLine', 'SingleLine'])->end()
                                ->arrayNode('label')
                                    ->isRequired()
                                    ->useAttributeAsKey('lang')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
            ->end()
        ->end();

        return $node;
    }
}
