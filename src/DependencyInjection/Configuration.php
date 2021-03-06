<?php

namespace BestIt\CTCustomTypesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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
        $builder = new TreeBuilder('types');
        $rootNode = $this->getRootNode($builder, 'best_it_ct_custom_types');

        $rootNode
            ->children()
                ->append($this->getTypesNode())
                ->arrayNode('whitelist')
                    ->info(
                        'The shell command works on the complete set of types normally. To prevent side effects ' .
                        'while changing or deleting types which are "unknown at this moment" define a whitelist for ' .
                        'types, on which you are allowed to work on.'
                    )
                    ->prototype('scalar')->end()
                ->end()
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
        $builder = new TreeBuilder('types');
        $node = $this->getRootNode($builder, 'types');

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
                                                'DateTime',
                                                'Set',
                                                'Reference',
                                            ])
                                        ->end()
                                        ->scalarNode('referenceTypeId')->end()
                                        ->arrayNode('elementType')
                                            ->info(
                                                'Specially used to the set type: <http://dev.commercetools.com/' .
                                                'http-api-projects-types.html#settype>'
                                            )
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
                                                        'DateTime',
                                                        'Set'
                                                    ])
                                                ->end()
                                                ->arrayNode('values')
                                                    ->useAttributeAsKey('key')
                                                    ->prototype('scalar')->end()
                                                ->end()
                                            ->end()
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

    /**
     * BC layer for symfony/config 4.1 and older
     *
     * @param TreeBuilder $treeBuilder
     * @param $name
     *
     * @return ArrayNodeDefinition|NodeDefinition
     */
    private function getRootNode(TreeBuilder $treeBuilder, $name)
    {
        if (!method_exists($treeBuilder, 'getRootNode')) {
            return $treeBuilder->root($name);
        }

        return $treeBuilder->getRootNode();
    }
}
