<?php declare(strict_types=1);

namespace Shopware\Core\Tools\Adapter\Twig\Extension;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Builder\BuilderRegistryInterface;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Shopware\Core\Framework\Log\Package;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[Package('core')]
final class QrCodeExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct(
        private readonly BuilderRegistryInterface $builderRegistry,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('qr_code_data_uri', $this->qrCodeDataUriFunction(...)),
            new TwigFunction('qr_code_result', $this->qrCodeResultFunction(...)),
        ];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \Exception
     */
    public function qrCodeDataUriFunction(string $data, string $builder = 'default', array $options = []): string
    {
        $result = $this->qrCodeResultFunction($data, $builder, $options);

        return $result->getDataUri();
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \Exception
     */
    public function qrCodeResultFunction(string $data, string $builder = 'default', array $options = []): ResultInterface
    {
        $builder = $this->builderRegistry->getBuilder($builder);

        foreach ($options as $option => $value) {
            if (!method_exists($builder, $option)) {
                throw new \Exception(sprintf('Builder option "%s" does not exist', $option));
            }
            $builder->$option($value);
        }

        if (!$builder instanceof Builder) {
            throw new \Exception('This twig extension only handles Builder instances');
        }

        return $builder->data($data)->build();
    }
}
