<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1536232600SystemDict extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232600;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `system_dict` (
              `id`                  BINARY(16)                              NOT NULL,
              `technical_name`      varchar(255) COLLATE utf8mb4_unicode_ci      NOT NULL,
              `plugin_id`           binary(16) DEFAULT NULL,
              `active` tinyint unsigned DEFAULT NULL,
              `data_type` varchar(64) NOT NULL DEFAULT \'list\',
              `created_at`          DATETIME(3)                             NOT NULL,
              `updated_at`          DATETIME(3)                             NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.technical_name` (`technical_name`),
              KEY `fk.system_dict.plugin_id` (`plugin_id`),
              CONSTRAINT `fk.system_dict.plugin_id` FOREIGN KEY (`plugin_id`)
                    REFERENCES `plugin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `system_dict_translation` (
              `system_dict_id`                  BINARY(16)                              NOT NULL,
              `language_id` binary(16) NOT NULL,
              `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`system_dict_id`,`language_id`),
              KEY `fk.system_dict_translation.language_id` (`language_id`),
              CONSTRAINT `fk.system_dict_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.system_dict_translation.system_dict_id` FOREIGN KEY (`system_dict_id`) REFERENCES `system_dict` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.system_dict_translation.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `system_dict_item` (
              `id`                  BINARY(16)                              NOT NULL,
              `system_dict_id`      BINARY(16)                              NOT NULL,
              `technical_name`      varchar(255) COLLATE utf8mb4_unicode_ci      NOT NULL,
              `active` tinyint unsigned DEFAULT NULL,
              `parent_id` binary(16) DEFAULT NULL,
              `position` int NOT NULL DEFAULT \'1\',
              `data_type` varchar(64) NOT NULL DEFAULT \'string\',
              `created_at`          DATETIME(3)                             NOT NULL,
              `updated_at`          DATETIME(3)                             NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.technical_name` (`technical_name`),
              CONSTRAINT `fk.system_dict_item.system_dict_id` FOREIGN KEY (`system_dict_id`)
                    REFERENCES `system_dict` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.system_dict_item.parent_id` FOREIGN KEY (`parent_id`)
                  REFERENCES `system_dict_item` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `system_dict_item_translation` (
              `system_dict_item_id`                  BINARY(16)                              NOT NULL,
              `language_id` binary(16) NOT NULL,
              `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`system_dict_item_id`,`language_id`),
              KEY `fk.system_dict_item_translation.language_id` (`language_id`),
              CONSTRAINT `fk.system_dict_item_translation.language_id` FOREIGN KEY (`language_id`)
                  REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.system_dict_item_translation.system_dict_item_id` FOREIGN KEY (`system_dict_item_id`) REFERENCES `system_dict` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.system_dict_item_translation.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
