<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition) {
    $definition->rootNode()
        ->children()
            ->arrayNode('queues')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('url')->end()
                        ->scalarNode('handler')->end()
                        ->scalarNode('max_messages')->defaultValue(1)->end()
                        ->scalarNode('wait_time')->defaultValue(20)->end()
                    ->end()
                ->end()
            ->end() // queues
        ->end()
    ;
};
