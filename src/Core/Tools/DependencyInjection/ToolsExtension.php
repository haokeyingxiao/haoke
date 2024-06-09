<?php declare(strict_types=1);

namespace Shopware\Core\Tools\DependencyInjection;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

#[Package('core')]
class ToolsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->addConfig($container, 'tools', $config);
    }

    private function addConfig(ContainerBuilder $container, string $alias, array $options): void
    {
        foreach ($options as $key => $option) {
            $container->setParameter($alias . '.' . $key, $option);

            if (\is_array($option)) {
                $this->addConfig($container, $alias . '.' . $key, $option);
            }
        }
    }
}
