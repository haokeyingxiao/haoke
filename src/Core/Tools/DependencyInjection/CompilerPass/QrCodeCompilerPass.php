<?php

declare(strict_types=1);

namespace Shopware\Core\Tools\DependencyInjection\CompilerPass;

use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Builder\BuilderRegistryInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Shopware\Core\Framework\DependencyInjection\CompilerPass\CompilerPassConfigTrait;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

#[Package('core')]
class QrCodeCompilerPass implements CompilerPassInterface
{
    use CompilerPassConfigTrait;

    public function process(ContainerBuilder $container): void
    {
        $config = $container->getParameter('tools.endroid_qr_code');
        $registryDefinition = $container->findDefinition(BuilderRegistryInterface::class);

        foreach ($config as $builderName => $builderConfig) {
            $builderDefinition = $this->createBuilderDefinition($builderName, $builderConfig, $container);
            $registryDefinition->addMethodCall('addBuilder', [$builderName, $builderDefinition]);
        }
    }

    private function createBuilderDefinition(string $builderName, array $builderConfig, ContainerBuilder $container): Definition
    {
        $id = sprintf('endroid_qr_code.%s_builder', $builderName);

        $builderDefinition = new ChildDefinition(BuilderInterface::class);

        $options = [];
        foreach ($builderConfig as $name => $value) {
            switch ($name) {
                case 'writer':
                    $options[$name] = new Reference($value);
                    break;
                case 'encoding':
                    $options[$name] = new Definition(Encoding::class, [$value]);
                    break;
                case 'errorCorrectionLevel':
                    $options[$name] = ErrorCorrectionLevel::from($value);
                    break;
                case 'roundBlockSizeMode':
                    $options[$name] = RoundBlockSizeMode::from($value);
                    break;
                case 'foregroundColor':
                case 'backgroundColor':
                case 'labelTextColor':
                    $options[$name] = new Definition(Color::class, $value);
                    break;
                case 'labelFontPath':
                    $labelFontSize = $builderConfig['labelFontSize'] ?? 16;
                    $options['labelFont'] = new Definition(Font::class, [$value, $labelFontSize]);
                    break;
                case 'labelFontSize':
                    $labelFontPath = $builderConfig['labelFontPath'] ?? (new NotoSans())->getPath();
                    $options['labelFont'] = new Definition(Font::class, [$labelFontPath, $value]);
                    break;
                case 'labelAlignment':
                    $options[$name] = LabelAlignment::from($value);
                    break;
                default:
                    $options[$name] = $value;
                    break;
            }
        }

        foreach ($options as $name => $value) {
            $builderDefinition->addMethodCall($name, [$value]);
            $builderDefinition->setPublic(true);
        }

        $container->setDefinition($id, $builderDefinition);

        $container->registerAliasForArgument($id, BuilderInterface::class, $builderName . 'QrCodeBuilder')->setPublic(false);

        return $builderDefinition;
    }
}
