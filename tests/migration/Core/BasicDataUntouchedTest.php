<?php declare(strict_types=1);

namespace Shopware\Tests\Migration\Core;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Migration\V6_3\Migration1536233560BasicData;

/**
 * @internal
 */
#[CoversNothing]
class BasicDataUntouchedTest extends TestCase
{
    public function testBasicDataUntouched(): void
    {
        $file = KernelLifecycleManager::getClassLoader()->findFile(Migration1536233560BasicData::class);
        static::assertIsString($file);

        static::assertSame(
            'b27d596e333d0dd7780df15a90027e890be8d55f',
            sha1_file($file),
            'BasicData migration has changed. This is not allowed.'
        );
    }
}
