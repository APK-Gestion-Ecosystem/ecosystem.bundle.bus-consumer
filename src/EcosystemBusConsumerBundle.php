<?php

namespace Ecosystem\BusConsumerBundle;

use Ecosystem\BusConsumerBundle\Service\ConsumerService;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class EcosystemBusConsumerBundle extends AbstractBundle
{
    public function loadExtension(
        array $config,
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder
    ): void {
        $containerConfigurator->import('../config/services.yaml');

        foreach ($config['queues'] as $name => $queueConfig) {
            $containerConfigurator->services()->get(ConsumerService::class)->call('addQueue', [
                $name,
                $queueConfig['url'],
                intval($queueConfig['max_messages']),
                intval($queueConfig['wait_time']),
                new Reference($queueConfig['handler'])
            ]);
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }
}
