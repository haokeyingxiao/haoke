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
            '4a431da4c17a672c6e490a085930069304b6ab31',
            sha1_file($file),
            'BasicData migration has changed. This is not allowed.'
        );
    }
}
