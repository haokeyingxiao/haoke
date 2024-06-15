<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1575021466AddCurrencies extends MigrationStep
{
    private ?string $deLanguage = null;

    private ?string $zhLanguage = null;

    private ?string $defaultLanguage = null;

    public function getCreationTimestamp(): int
    {
        return 1575021466;
    }

    public function update(Connection $connection): void
    {
        $this->createCurrencyUniqueConstraint($connection);
        $this->createCurrencies($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function createCurrencyUniqueConstraint(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `currency` ADD  CONSTRAINT `uniq.currency.iso_code` UNIQUE (`iso_code`)');
    }

    private function createCurrencies(Connection $connection): void
    {
        $this->addCurrency($connection, Uuid::randomBytes(), 'PLN', 0.56, 'zł', 'PLN', 'PLN', 'PLN', 'Złoty', 'Złoty', '波兰兹罗特');
        $this->addCurrency($connection, Uuid::randomBytes(), 'CHF', 0.1, 'Fr', 'CHF', 'CHF', 'CHF', 'Schweizer Franken', 'Swiss francs', '瑞士法郎');
        $this->addCurrency($connection, Uuid::randomBytes(), 'SEK', 0.69, 'kr', 'SEK', 'SEK', 'SEK', 'Schwedische Kronen', 'Swedish krone', '瑞典克朗');
        $this->addCurrency($connection, Uuid::randomBytes(), 'DKK', 0.96, 'kr', 'DKK', 'DKK', 'DKK', 'Dänische Kronen', 'Danish krone', '丹麦克朗');
        $this->addCurrency($connection, Uuid::randomBytes(), 'NOK', 1.471, 'nkr', 'NOK', 'NOK', 'NOK', 'Norwegische Kronen', 'Norwegian krone', '挪威克朗');
    }

    private function addCurrency(
        Connection $connection,
        string $id,
        string $isoCode,
        float $factor,
        string $symbol,
        string $shortNameDe,
        string $shortNameEn,
        string $shortNameZh,
        string $nameDe,
        string $nameEn,
        string $nameZh,
    ): void {
        $languageEN = $this->getEnLanguageId($connection);
        $languageDE = $this->getDeLanguageId($connection);
        $languageZH = $this->getZhLanguageId($connection);

        $langId = $connection->fetchOne('
        SELECT `currency`.`id` FROM `currency` WHERE `iso_code` = :code LIMIT 1
        ', ['code' => $isoCode]);

        if (!$langId) {
            $connection->insert('currency', ['id' => $id, 'iso_code' => $isoCode, 'factor' => $factor, 'symbol' => $symbol, 'position' => 1, 'decimal_precision' => 2, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
            if ($languageEN !== $languageDE && $languageEN !== $languageZH) {
                $connection->insert('currency_translation', ['currency_id' => $id, 'language_id' => $languageEN, 'short_name' => $shortNameEn, 'name' => $nameEn, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
            }
            if ($languageZH) {
                $connection->insert('currency_translation', ['currency_id' => $id, 'language_id' => $languageZH, 'short_name' => $shortNameZh, 'name' => $nameZh, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
            }
            if ($languageDE) {
                $connection->insert('currency_translation', ['currency_id' => $id, 'language_id' => $languageDE, 'short_name' => $shortNameDe, 'name' => $nameDe, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
            }
        }
    }

    private function getDeLanguageId(Connection $connection): ?string
    {
        if (!$this->deLanguage) {
            $this->deLanguage = $this->fetchLanguageId('de-DE', $connection);
        }

        return $this->deLanguage;
    }

    private function getZhLanguageId(Connection $connection): ?string
    {
        if (!$this->zhLanguage) {
            $this->zhLanguage = $this->fetchLanguageId('zh-CN', $connection);
        }

        return $this->zhLanguage;
    }

    private function getEnLanguageId(Connection $connection): ?string
    {
        if (!$this->defaultLanguage) {
            $this->defaultLanguage = $this->fetchLanguageId('en-GB', $connection);
        }

        return $this->defaultLanguage;
    }

    private function fetchLanguageId(string $code, Connection $connection): ?string
    {
        $langId = $connection->fetchOne('
        SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`translation_code_id` = `locale`.`id` WHERE `code` = :code LIMIT 1
        ', ['code' => $code]);

        if (!$langId && $code !== 'en-GB') {
            return null;
        }

        if (!$langId) {
            return Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        }

        return $langId;
    }
}
