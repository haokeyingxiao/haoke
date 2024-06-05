<?php declare(strict_types=1);

namespace Shopware\Core\Tools\DependencyInjection;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

#[Package('core')]
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tools');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->append($this->createEndroidQrCodeNode())
            ->end();

        return $treeBuilder;
    }

    private function createEndroidQrCodeNode(): ArrayNodeDefinition
    {
        $rootNode = (new TreeBuilder('endroid_qr_code'))->getRootNode();
        $rootNode
            ->beforeNormalization()
            ->ifTrue(fn (array $config) => !$this->hasMultipleConfigurations($config))
            ->then(fn (array $value) => ['default' => $value]);

        $rootNode->useAttributeAsKey('name');
        $rootNode->prototype('array');
        $rootNode->prototype('variable');

        return $rootNode;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function hasMultipleConfigurations(array $config): bool
    {
        foreach ($config as $value) {
            if (!\is_array($value)) {
                return false;
            }
        }

        return true;
    }
}
