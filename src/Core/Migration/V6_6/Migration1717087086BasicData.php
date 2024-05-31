<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('Core')]
class Migration1717087086BasicData extends MigrationStep
{
    private ?string $zhCnLanguageId = null;
    private ?string $deDeLanguageId = null;

    public function getCreationTimestamp(): int
    {
        return 1717087086;
    }

    public function update(Connection $connection): void
    {
        $this->createLanguage($connection);
        $this->transSalutation($connection);
        $this->transCountry($connection);
        $this->createCurrency($connection);
        $this->transCustomerGroup($connection);
    }

    private function transCustomerGroup(Connection $connection): void
    {
        $connection->insert('customer_group_translation', ['customer_group_id' => Uuid::fromHexToBytes('cfbd5018d38d41d8adca10d94fc8bdd6'), 'language_id' => Uuid::fromHexToBytes($this->getZhCnLanguageId()), 'name' => '标准客户组', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
    }

    private function createCurrency(Connection $connection): void
    {
        $CNY = Uuid::randomBytes();
        $languageZH = Uuid::fromHexToBytes($this->getZhCnLanguageId());
        $languageEN = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $languageDE = $this->getDeDeLanguageId($connection);
        $connection->insert('currency', ['id' => $CNY, 'iso_code' => 'CNY', 'factor' => 1, 'symbol' => '¥', 'position' => 1, 'decimal_precision' => 2, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('currency_translation', ['currency_id' => $CNY, 'language_id' => $languageEN, 'short_name' => 'CNY', 'name' => 'CNY', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('currency_translation', ['currency_id' => $CNY, 'language_id' => $languageZH, 'short_name' => 'CNY', 'name' => '人民币', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
        $connection->insert('currency_translation', ['currency_id' => $CNY, 'language_id' => $languageDE, 'short_name' => 'CNY', 'name' => 'CNY', 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
    }

    private function transCountry($connection): void
    {
        $languageZH = fn(string $countryId, string $name) => [
            'language_id' => Uuid::fromHexToBytes($this->getZhCnLanguageId()),
            'name' => $name,
            'country_id' => $countryId,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];
        $cnId = $connection->fetchOne('SELECT id FROM country where iso3 = :iso3 LIMIT 1', ['iso3' => 'CHN']);
        $connection->insert('country_translation', $languageZH($cnId, '中国'));

    }

    private function transSalutation($connection): void
    {
        $languageZh = Uuid::fromHexToBytes($this->getZhCnLanguageId());
        $mr = $connection->fetchOne('SELECT id FROM salutation where salutation_key = :salutation_key LIMIT 1', ['salutation_key' => 'mr']);
        $connection->insert('salutation_translation', [
            'salutation_id' => $mr,
            'language_id' => $languageZh,
            'display_name' => '先生.',
            'letter_name' => '尊敬的先生.',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $mrs = $connection->fetchOne('SELECT id FROM salutation where salutation_key = :salutation_key LIMIT 1', ['salutation_key' => 'mrs']);
        $connection->insert('salutation_translation', [
            'salutation_id' => $mrs,
            'language_id' => $languageZh,
            'display_name' => '女士.',
            'letter_name' => '尊敬的女士.',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $notSpecified = $connection->fetchOne('SELECT id FROM salutation where salutation_key = :salutation_key LIMIT 1', ['salutation_key' => 'not_specified']);
        $connection->insert('salutation_translation', [
            'salutation_id' => $notSpecified,
            'language_id' => $languageZh,
            'display_name' => '未知',
            'letter_name' => '',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function createLanguage(Connection $connection): void
    {
        $languageZh = Uuid::fromHexToBytes($this->getZhCnLanguageId());

        $localeZh = $connection->fetchOne('SELECT id FROM locale where code = :code LIMIT 1', ['code' => 'zh-CN']);
        $connection->insert('language', [
            'id' => $languageZh,
            'name' => '中文',
            'locale_id' => $localeZh,
            'translation_code_id' => $localeZh,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $connection->insert('locale_translation', [
            'locale_id' => $localeZh,
            'language_id' => $languageZh,
            'name' => '中文',
            'territory' => '中国',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function getZhCnLanguageId(): ?string
    {
        if (!$this->zhCnLanguageId) {
            $this->zhCnLanguageId = Uuid::randomHex();
        }
        return $this->zhCnLanguageId;
    }

    private function getDeDeLanguageId(Connection $connection): string
    {
        if (!$this->deDeLanguageId) {
            $this->deDeLanguageId = $connection->fetchOne('SELECT id FROM language where name = :name LIMIT 1', ['name' => 'Deutsch']);
        }
        return $this->deDeLanguageId;
    }
}
