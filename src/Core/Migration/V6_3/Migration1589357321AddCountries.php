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
class Migration1589357321AddCountries extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589357321;
    }

    public function update(Connection $connection): void
    {
        $deLanguageId = $this->getLanguageId($connection, 'de-DE');
        $languageDE = null;
        if ($deLanguageId && $deLanguageId !== Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)) {
            $languageDE = static fn (string $countryId, string $name) => [
                'language_id' => $deLanguageId,
                'name' => $name,
                'country_id' => $countryId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];
        }

        $zhLanguageId = $this->getLanguageId($connection, 'zh-CN');
        $languageZH = null;
        if ($zhLanguageId && $zhLanguageId !== Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)) {
            $languageZH = static fn (string $countryId, string $name) => [
                'language_id' => $zhLanguageId,
                'name' => $name,
                'country_id' => $countryId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];
        }

        $enLanguageId = $this->getLanguageId($connection, 'en-GB');
        $languageEN = null;
        if ($enLanguageId && $enLanguageId !== Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)) {
            $languageEN = static fn (string $countryId, string $name) => [
                'language_id' => $enLanguageId,
                'name' => $name,
                'country_id' => $countryId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];
        }

        $default = static fn (string $countryId, string $name) => [
            'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            'name' => $name,
            'country_id' => $countryId,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        foreach ($this->createNewCountries() as $country) {
            $id = Uuid::randomBytes();
            $exists = $connection->fetchOne('SELECT 1 FROM country WHERE iso = :iso3', ['iso3' => $country['iso3']]);
            if ($exists !== false) {
                continue;
            }

            $connection->insert('country', ['id' => $id, 'iso' => $country['iso'], 'position' => 10, 'iso3' => $country['iso3'], 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
            $defaultTranslations = $country['en'];
            if ($deLanguageId === Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)) {
                $defaultTranslations = $country['de'];
            }
            $connection->insert('country_translation', $default($id, $defaultTranslations));

            if ($languageDE !== null) {
                $connection->insert('country_translation', $languageDE($id, $country['de']));
            }
            if ($languageEN !== null) {
                $connection->insert('country_translation', $languageEN($id, $country['en']));
            }
            if ($languageZH !== null) {
                $connection->insert('country_translation', $languageZH($id, $country['zh']));
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function getLanguageId(Connection $connection, string $code): string
    {
        $sql = <<<'SQL'
            SELECT id
            FROM `language`
            WHERE translation_code_id = (
               SELECT id
               FROM locale
               WHERE locale.code = :code
            )
            ORDER BY created_at ASC
SQL;

        return (string) $connection->executeQuery($sql, ['code' => $code])->fetchOne();
    }

    /**
     * @return list<array{iso: string, iso3: string, de: string, en: string, zh:string}>
     */
    private function createNewCountries(): array
    {
        return [
            [
                'iso' => 'BG',
                'iso3' => 'BGR',
                'de' => 'Bulgarien',
                'en' => 'Bulgaria',
                'zh' => '保加利亚',
            ], [
                'iso' => 'EE',
                'iso3' => 'EST',
                'de' => 'Estland',
                'en' => 'Estonia',
                'zh' => '爱沙尼亚',
            ], [
                'iso' => 'HR',
                'iso3' => 'HRV',
                'de' => 'Kroatien',
                'en' => 'Croatia',
                'zh' => '克罗地亚',
            ], [
                'iso' => 'LV',
                'iso3' => 'LVA',
                'de' => 'Lettland',
                'en' => 'Latvia',
                'zh' => '拉脱维亚',
            ], [
                'iso' => 'LT',
                'iso3' => 'LTU',
                'de' => 'Litauen',
                'en' => 'Lithuania',
                'zh' => '立陶宛',
            ], [
                'iso' => 'MT',
                'iso3' => 'MLT',
                'de' => 'Malta',
                'en' => 'Malta',
                'zh' => '马耳他',
            ], [
                'iso' => 'SI',
                'iso3' => 'SVN',
                'de' => 'Slowenien',
                'en' => 'Slovenia',
                'zh' => '斯洛文尼亚',
            ], [
                'iso' => 'CY',
                'iso3' => 'CYP',
                'de' => 'Zypern',
                'en' => 'Cyprus',
                'zh' => '塞浦路斯',
            ], [
                'iso' => 'AF',
                'iso3' => 'AFG',
                'de' => 'Afghanistan',
                'en' => 'Afghanistan',
                'zh' => '阿富汗',
            ], [
                'iso' => 'AX',
                'iso3' => 'ALA',
                'de' => 'Åland',
                'en' => 'Åland Islands',
                'zh' => '奥兰群岛',
            ], [
                'iso' => 'AL',
                'iso3' => 'ALB',
                'de' => 'Albanien',
                'en' => 'Albania',
                'zh' => '阿尔巴尼亚',
            ], [
                'iso' => 'DZ',
                'iso3' => 'DZA',
                'de' => 'Algerien',
                'en' => 'Algeria',
                'zh' => '阿尔及利亚',
            ], [
                'iso' => 'AS',
                'iso3' => 'ASM',
                'de' => 'Amerikanisch-Samoa',
                'en' => 'American Samoa',
                'zh' => '美属萨摩亚',
            ], [
                'iso' => 'AD',
                'iso3' => 'AND',
                'de' => 'Andorra',
                'en' => 'Andorra',
                'zh' => '安道尔',
            ], [
                'iso' => 'AO',
                'iso3' => 'AGO',
                'de' => 'Angola',
                'en' => 'Angola',
                'zh' => '安哥拉',
            ], [
                'iso' => 'AI',
                'iso3' => 'AIA',
                'de' => 'Anguilla',
                'en' => 'Anguilla',
                'zh' => '安圭拉',
            ], [
                'iso' => 'AQ',
                'iso3' => 'ATA',
                'de' => 'Antarktika',
                'en' => 'Antarctica',
                'zh' => '南极洲',
            ], [
                'iso' => 'AG',
                'iso3' => 'ATG',
                'de' => 'Antigua und Barbuda',
                'en' => 'Antigua and Barbuda',
                'zh' => '安提瓜和巴布达',
            ], [
                'iso' => 'AR',
                'iso3' => 'ARG',
                'de' => 'Argentinien',
                'en' => 'Argentina',
                'zh' => '阿根廷',
            ], [
                'iso' => 'AM',
                'iso3' => 'ARM',
                'de' => 'Armenien',
                'en' => 'Armenia',
                'zh' => '亚美尼亚',
            ], [
                'iso' => 'AW',
                'iso3' => 'ABW',
                'de' => 'Aruba',
                'en' => 'Aruba',
                'zh' => '阿鲁巴',
            ], [
                'iso' => 'AZ',
                'iso3' => 'AZE',
                'de' => 'Aserbaidschan',
                'en' => 'Azerbaijan',
                'zh' => '阿塞拜疆',
            ], [
                'iso' => 'BS',
                'iso3' => 'BHS',
                'de' => 'Bahamas',
                'en' => 'Bahamas',
                'zh' => '巴哈马',
            ], [
                'iso' => 'BH',
                'iso3' => 'BHR',
                'de' => 'Bahrain',
                'en' => 'Bahrain',
                'zh' => '巴林',
            ], [
                'iso' => 'BD',
                'iso3' => 'BGD',
                'de' => 'Bangladesch',
                'en' => 'Bangladesh',
                'zh' => '孟加拉国',
            ], [
                'iso' => 'BB',
                'iso3' => 'BRB',
                'de' => 'Barbados',
                'en' => 'Barbados',
                'zh' => '巴巴多斯',
            ], [
                'iso' => 'BY',
                'iso3' => 'BLR',
                'de' => 'Weißrussland',
                'en' => 'Belarus',
                'zh' => '白俄罗斯',
            ], [
                'iso' => 'BZ',
                'iso3' => 'BLZ',
                'de' => 'Belize',
                'en' => 'Belize',
                'zh' => '伯利兹',
            ], [
                'iso' => 'BJ',
                'iso3' => 'BEN',
                'de' => 'Benin',
                'en' => 'Benin',
                'zh' => '贝宁',
            ], [
                'iso' => 'BM',
                'iso3' => 'BMU',
                'de' => 'Bermuda',
                'en' => 'Bermuda',
                'zh' => '百慕大',
            ], [
                'iso' => 'BT',
                'iso3' => 'BTN',
                'de' => 'Bhutan',
                'en' => 'Bhutan',
                'zh' => '不丹',
            ], [
                'iso' => 'BO',
                'iso3' => 'BOL',
                'de' => 'Bolivien',
                'en' => 'Bolivia (Plurinational State of)',
                'zh' => '玻利维亚',
            ], [
                'iso' => 'BQ',
                'iso3' => 'BES',
                'de' => 'Bonaire, Sint Eustatius und Saba',
                'en' => 'Bonaire, Sint Eustatius and Saba',
                'zh' => '博奈尔、圣尤斯特歇斯和萨巴',
            ], [
                'iso' => 'BA',
                'iso3' => 'BIH',
                'de' => 'Bosnien und Herzegowina',
                'en' => 'Bosnia and Herzegovina',
                'zh' => '波斯尼亚和黑塞哥维那',
            ], [
                'iso' => 'BW',
                'iso3' => 'BWA',
                'de' => 'Botswana',
                'en' => 'Botswana',
                'zh' => '博茨瓦纳',
            ], [
                'iso' => 'BV',
                'iso3' => 'BVT',
                'de' => 'Bouvetinsel',
                'en' => 'Bouvet Island',
                'zh' => '布韦岛',
            ], [
                'iso' => 'IO',
                'iso3' => 'IOT',
                'de' => 'Britisches Territorium im Indischen Ozean',
                'en' => 'British Indian Ocean Territory',
                'zh' => '英属印度洋领地',
            ], [
                'iso' => 'UM',
                'iso3' => 'UMI',
                'de' => 'Kleinere Inselbesitzungen der Vereinigten Staaten',
                'en' => 'United States Minor Outlying Islands',
                'zh' => '美国边远小岛',
            ], [
                'iso' => 'VG',
                'iso3' => 'VGB',
                'de' => 'Britische Jungferninseln',
                'en' => 'Virgin Islands (British)',
                'zh' => '英属维尔京群岛',
            ], [
                'iso' => 'VI',
                'iso3' => 'VIR',
                'de' => 'Amerikanische Jungferninseln',
                'en' => 'Virgin Islands (U.S.)',
                'zh' => '美属维尔京群岛',
            ], [
                'iso' => 'BN',
                'iso3' => 'BRN',
                'de' => 'Brunei',
                'en' => 'Brunei Darussalam',
                'zh' => '文莱',
            ], [
                'iso' => 'BF',
                'iso3' => 'BFA',
                'de' => 'Burkina Faso',
                'en' => 'Burkina Faso',
                'zh' => '布基纳法索',
            ], [
                'iso' => 'BI',
                'iso3' => 'BDI',
                'de' => 'Burundi',
                'en' => 'Burundi',
                'zh' => '布隆迪',
            ], [
                'iso' => 'KH',
                'iso3' => 'KHM',
                'de' => 'Kambodscha',
                'en' => 'Cambodia',
                'zh' => '柬埔寨',
            ], [
                'iso' => 'CM',
                'iso3' => 'CMR',
                'de' => 'Kamerun',
                'en' => 'Cameroon',
                'zh' => '喀麦隆',
            ], [
                'iso' => 'CV',
                'iso3' => 'CPV',
                'de' => 'Kap Verde',
                'en' => 'Cabo Verde',
                'zh' => '佛得角',
            ], [
                'iso' => 'KY',
                'iso3' => 'CYM',
                'de' => 'Kaimaninseln',
                'en' => 'Cayman Islands',
                'zh' => '开曼群岛',
            ], [
                'iso' => 'CF',
                'iso3' => 'CAF',
                'de' => 'Zentralafrikanische Republik',
                'en' => 'Central African Republic',
                'zh' => '中非共和国',
            ], [
                'iso' => 'TD',
                'iso3' => 'TCD',
                'de' => 'Tschad',
                'en' => 'Chad',
                'zh' => '乍得',
            ], [
                'iso' => 'CL',
                'iso3' => 'CHL',
                'de' => 'Chile',
                'en' => 'Chile',
                'zh' => '智利',
            ], [
                'iso' => 'CX',
                'iso3' => 'CXR',
                'de' => 'Weihnachtsinsel',
                'en' => 'Christmas Island',
                'zh' => '圣诞岛',
            ], [
                'iso' => 'CC',
                'iso3' => 'CCK',
                'de' => 'Kokosinseln',
                'en' => 'Cocos (Keeling) Islands',
                'zh' => '科科斯（基林）群岛',
            ], [
                'iso' => 'CO',
                'iso3' => 'COL',
                'de' => 'Kolumbien',
                'en' => 'Colombia',
                'zh' => '哥伦比亚',
            ], [
                'iso' => 'KM',
                'iso3' => 'COM',
                'de' => 'Union der Komoren',
                'en' => 'Comoros',
                'zh' => '科摩罗',
            ], [
                'iso' => 'CG',
                'iso3' => 'COG',
                'de' => 'Kongo',
                'en' => 'Congo',
                'zh' => '刚果',
            ], [
                'iso' => 'CD',
                'iso3' => 'COD',
                'de' => 'Kongo (Dem. Rep.)',
                'en' => 'Congo (Democratic Republic of the)',
                'zh' => '刚果（金）',
            ], [
                'iso' => 'CK',
                'iso3' => 'COK',
                'de' => 'Cookinseln',
                'en' => 'Cook Islands',
                'zh' => '库克群岛',
            ], [
                'iso' => 'CR',
                'iso3' => 'CRI',
                'de' => 'Costa Rica',
                'en' => 'Costa Rica',
                'zh' => '哥斯达黎加',
            ], [
                'iso' => 'CU',
                'iso3' => 'CUB',
                'de' => 'Kuba',
                'en' => 'Cuba',
                'zh' => '古巴',
            ], [
                'iso' => 'CW',
                'iso3' => 'CUW',
                'de' => 'Curaçao',
                'en' => 'Curaçao',
                'zh' => '库拉索',
            ], [
                'iso' => 'DJ',
                'iso3' => 'DJI',
                'de' => 'Dschibuti',
                'en' => 'Djibouti',
                'zh' => '吉布提',
            ], [
                'iso' => 'DM',
                'iso3' => 'DMA',
                'de' => 'Dominica',
                'en' => 'Dominica',
                'zh' => '多米尼克',
            ], [
                'iso' => 'DO',
                'iso3' => 'DOM',
                'de' => 'Dominikanische Republik',
                'en' => 'Dominican Republic',
                'zh' => '多米尼加共和国',
            ], [
                'iso' => 'EC',
                'iso3' => 'ECU',
                'de' => 'Ecuador',
                'en' => 'Ecuador',
                'zh' => '厄瓜多尔',
            ], [
                'iso' => 'EG',
                'iso3' => 'EGY',
                'de' => 'Ägypten',
                'en' => 'Egypt',
                'zh' => '埃及',
            ], [
                'iso' => 'SV',
                'iso3' => 'SLV',
                'de' => 'El Salvador',
                'en' => 'El Salvador',
                'zh' => '萨尔瓦多',
            ], [
                'iso' => 'GQ',
                'iso3' => 'GNQ',
                'de' => 'Äquatorial-Guinea',
                'en' => 'Equatorial Guinea',
                'zh' => '赤道几内亚',
            ], [
                'iso' => 'ER',
                'iso3' => 'ERI',
                'de' => 'Eritrea',
                'en' => 'Eritrea',
                'zh' => '厄立特里亚',
            ], [
                'iso' => 'ET',
                'iso3' => 'ETH',
                'de' => 'Äthiopien',
                'en' => 'Ethiopia',
                'zh' => '埃塞俄比亚',
            ], [
                'iso' => 'FK',
                'iso3' => 'FLK',
                'de' => 'Falklandinseln',
                'en' => 'Falkland Islands (Malvinas)',
                'zh' => '福克兰群岛（马尔维纳斯群岛）',
            ], [
                'iso' => 'FO',
                'iso3' => 'FRO',
                'de' => 'Färöer-Inseln',
                'en' => 'Faroe Islands',
                'zh' => '法罗群岛',
            ], [
                'iso' => 'FJ',
                'iso3' => 'FJI',
                'de' => 'Fidschi',
                'en' => 'Fiji',
                'zh' => '斐济',
            ], [
                'iso' => 'GF',
                'iso3' => 'GUF',
                'de' => 'Französisch Guyana',
                'en' => 'French Guiana',
                'zh' => '法属圭亚那',
            ], [
                'iso' => 'PF',
                'iso3' => 'PYF',
                'de' => 'Französisch-Polynesien',
                'en' => 'French Polynesia',
                'zh' => '法属波利尼西亚',
            ], [
                'iso' => 'TF',
                'iso3' => 'ATF',
                'de' => 'Französische Süd- und Antarktisgebiete',
                'en' => 'French Southern Territories',
                'zh' => '法属南部领地',
            ], [
                'iso' => 'GA',
                'iso3' => 'GAB',
                'de' => 'Gabun',
                'en' => 'Gabon',
                'zh' => '加蓬',
            ], [
                'iso' => 'GM',
                'iso3' => 'GMB',
                'de' => 'Gambia',
                'en' => 'Gambia',
                'zh' => '冈比亚',
            ], [
                'iso' => 'GE',
                'iso3' => 'GEO',
                'de' => 'Georgien',
                'en' => 'Georgia',
                'zh' => '格鲁吉亚',
            ], [
                'iso' => 'GH',
                'iso3' => 'GHA',
                'de' => 'Ghana',
                'en' => 'Ghana',
                'zh' => '加纳',
            ], [
                'iso' => 'GI',
                'iso3' => 'GIB',
                'de' => 'Gibraltar',
                'en' => 'Gibraltar',
                'zh' => '直布罗陀',
            ], [
                'iso' => 'GL',
                'iso3' => 'GRL',
                'de' => 'Grönland',
                'en' => 'Greenland',
                'zh' => '格陵兰',
            ], [
                'iso' => 'GD',
                'iso3' => 'GRD',
                'de' => 'Grenada',
                'en' => 'Grenada',
                'zh' => '格林纳达',
            ], [
                'iso' => 'GP',
                'iso3' => 'GLP',
                'de' => 'Guadeloupe',
                'en' => 'Guadeloupe',
                'zh' => '瓜德罗普',
            ], [
                'iso' => 'GU',
                'iso3' => 'GUM',
                'de' => 'Guam',
                'en' => 'Guam',
                'zh' => '关岛',
            ], [
                'iso' => 'GT',
                'iso3' => 'GTM',
                'de' => 'Guatemala',
                'en' => 'Guatemala',
                'zh' => '危地马拉',
            ], [
                'iso' => 'GG',
                'iso3' => 'GGY',
                'de' => 'Guernsey',
                'en' => 'Guernsey',
                'zh' => '根西岛',
            ], [
                'iso' => 'GN',
                'iso3' => 'GIN',
                'de' => 'Guinea',
                'en' => 'Guinea',
                'zh' => '几内亚',
            ], [
                'iso' => 'GW',
                'iso3' => 'GNB',
                'de' => 'Guinea-Bissau',
                'en' => 'Guinea-Bissau',
                'zh' => '几内亚比绍',
            ], [
                'iso' => 'GY',
                'iso3' => 'GUY',
                'de' => 'Guyana',
                'en' => 'Guyana',
                'zh' => '圭亚那',
            ], [
                'iso' => 'HT',
                'iso3' => 'HTI',
                'de' => 'Haiti',
                'en' => 'Haiti',
                'zh' => '海地',
            ], [
                'iso' => 'HM',
                'iso3' => 'HMD',
                'de' => 'Heard und die McDonaldinseln',
                'en' => 'Heard Island and McDonald Islands',
                'zh' => '赫德岛和麦克唐纳群岛',
            ], [
                'iso' => 'VA',
                'iso3' => 'VAT',
                'de' => 'Heiliger Stuhl',
                'en' => 'Holy See',
                'zh' => '梵蒂冈',
            ], [
                'iso' => 'HN',
                'iso3' => 'HND',
                'de' => 'Honduras',
                'en' => 'Honduras',
                'zh' => '洪都拉斯',
            ], [
                'iso' => 'HK',
                'iso3' => 'HKG',
                'de' => 'Hong Kong',
                'en' => 'Hong Kong',
                'zh' => '香港',
            ], [
                'iso' => 'IN',
                'iso3' => 'IND',
                'de' => 'Indien',
                'en' => 'India',
                'zh' => '印度',
            ], [
                'iso' => 'ID',
                'iso3' => 'IDN',
                'de' => 'Indonesien',
                'en' => 'Indonesia',
                'zh' => '印度尼西亚',
            ], [
                'iso' => 'CI',
                'iso3' => 'CIV',
                'de' => 'Elfenbeinküste',
                'en' => 'Côte d\'Ivoire',
                'zh' => '科特迪瓦',
            ], [
                'iso' => 'IR',
                'iso3' => 'IRN',
                'de' => 'Iran',
                'en' => 'Iran (Islamic Republic of)',
                'zh' => '伊朗',
            ], [
                'iso' => 'IQ',
                'iso3' => 'IRQ',
                'de' => 'Irak',
                'en' => 'Iraq',
                'zh' => '伊拉克',
            ], [
                'iso' => 'IM',
                'iso3' => 'IMN',
                'de' => 'Insel Man',
                'en' => 'Isle of Man',
                'zh' => '马恩岛',
            ], [
                'iso' => 'JM',
                'iso3' => 'JAM',
                'de' => 'Jamaika',
                'en' => 'Jamaica',
                'zh' => '牙买加',
            ], [
                'iso' => 'JE',
                'iso3' => 'JEY',
                'de' => 'Jersey',
                'en' => 'Jersey',
                'zh' => '泽西岛',
            ], [
                'iso' => 'JO',
                'iso3' => 'JOR',
                'de' => 'Jordanien',
                'en' => 'Jordan',
                'zh' => '约旦',
            ], [
                'iso' => 'KZ',
                'iso3' => 'KAZ',
                'de' => 'Kasachstan',
                'en' => 'Kazakhstan',
                'zh' => '哈萨克斯坦',
            ], [
                'iso' => 'KE',
                'iso3' => 'KEN',
                'de' => 'Kenia',
                'en' => 'Kenya',
                'zh' => '肯尼亚',
            ], [
                'iso' => 'KI',
                'iso3' => 'KIR',
                'de' => 'Kiribati',
                'en' => 'Kiribati',
                'zh' => '基里巴斯',
            ], [
                'iso' => 'KW',
                'iso3' => 'KWT',
                'de' => 'Kuwait',
                'en' => 'Kuwait',
                'zh' => '科威特',
            ], [
                'iso' => 'KG',
                'iso3' => 'KGZ',
                'de' => 'Kirgisistan',
                'en' => 'Kyrgyzstan',
                'zh' => '吉尔吉斯斯坦',
            ], [
                'iso' => 'LA',
                'iso3' => 'LAO',
                'de' => 'Laos',
                'en' => 'Lao People\'s Democratic Republic',
                'zh' => '老挝',
            ], [
                'iso' => 'LB',
                'iso3' => 'LBN',
                'de' => 'Libanon',
                'en' => 'Lebanon',
                'zh' => '黎巴嫩',
            ], [
                'iso' => 'LS',
                'iso3' => 'LSO',
                'de' => 'Lesotho',
                'en' => 'Lesotho',
                'zh' => '莱索托',
            ], [
                'iso' => 'LR',
                'iso3' => 'LBR',
                'de' => 'Liberia',
                'en' => 'Liberia',
                'zh' => '利比里亚',
            ], [
                'iso' => 'LY',
                'iso3' => 'LBY',
                'de' => 'Libyen',
                'en' => 'Libya',
                'zh' => '利比亚',
            ], [
                'iso' => 'MO',
                'iso3' => 'MAC',
                'de' => 'Macao',
                'en' => 'Macao',
                'zh' => '澳门',
            ], [
                'iso' => 'MK',
                'iso3' => 'MKD',
                'de' => 'Mazedonien',
                'en' => 'Macedonia (the former Yugoslav Republic of)',
                'zh' => '马其顿',
            ], [
                'iso' => 'MG',
                'iso3' => 'MDG',
                'de' => 'Madagaskar',
                'en' => 'Madagascar',
                'zh' => '马达加斯加',
            ], [
                'iso' => 'MW',
                'iso3' => 'MWI',
                'de' => 'Malawi',
                'en' => 'Malawi',
                'zh' => '马拉维',
            ], [
                'iso' => 'MY',
                'iso3' => 'MYS',
                'de' => 'Malaysia',
                'en' => 'Malaysia',
                'zh' => '马来西亚',
            ], [
                'iso' => 'MV',
                'iso3' => 'MDV',
                'de' => 'Malediven',
                'en' => 'Maldives',
                'zh' => '马尔代夫',
            ], [
                'iso' => 'ML',
                'iso3' => 'MLI',
                'de' => 'Mali',
                'en' => 'Mali',
                'zh' => '马里',
            ], [
                'iso' => 'MH',
                'iso3' => 'MHL',
                'de' => 'Marshallinseln',
                'en' => 'Marshall Islands',
                'zh' => '马绍尔群岛',
            ], [
                'iso' => 'MQ',
                'iso3' => 'MTQ',
                'de' => 'Martinique',
                'en' => 'Martinique',
                'zh' => '马提尼克',
            ], [
                'iso' => 'MR',
                'iso3' => 'MRT',
                'de' => 'Mauretanien',
                'en' => 'Mauritania',
                'zh' => '毛里塔尼亚',
            ], [
                'iso' => 'MU',
                'iso3' => 'MUS',
                'de' => 'Mauritius',
                'en' => 'Mauritius',
                'zh' => '毛里求斯',
            ], [
                'iso' => 'YT',
                'iso3' => 'MYT',
                'de' => 'Mayotte',
                'en' => 'Mayotte',
                'zh' => '马约特',
            ], [
                'iso' => 'MX',
                'iso3' => 'MEX',
                'de' => 'Mexiko',
                'en' => 'Mexico',
                'zh' => '墨西哥',
            ], [
                'iso' => 'FM',
                'iso3' => 'FSM',
                'de' => 'Mikronesien',
                'en' => 'Micronesia (Federated States of)',
                'zh' => '密克罗尼西亚（联邦）',
            ], [
                'iso' => 'MD',
                'iso3' => 'MDA',
                'de' => 'Moldawie',
                'en' => 'Moldova (Republic of)',
                'zh' => '摩尔多瓦（共和国）',
            ], [
                'iso' => 'MC',
                'iso3' => 'MCO',
                'de' => 'Monaco',
                'en' => 'Monaco',
                'zh' => '摩纳哥',
            ], [
                'iso' => 'MN',
                'iso3' => 'MNG',
                'de' => 'Mongolei',
                'en' => 'Mongolia',
                'zh' => '蒙古',
            ], [
                'iso' => 'ME',
                'iso3' => 'MNE',
                'de' => 'Montenegro',
                'en' => 'Montenegro',
                'zh' => '黑山',
            ], [
                'iso' => 'MS',
                'iso3' => 'MSR',
                'de' => 'Montserrat',
                'en' => 'Montserrat',
                'zh' => '蒙特塞拉特',
            ], [
                'iso' => 'MA',
                'iso3' => 'MAR',
                'de' => 'Marokko',
                'en' => 'Morocco',
                'zh' => '摩洛哥',
            ], [
                'iso' => 'MZ',
                'iso3' => 'MOZ',
                'de' => 'Mosambik',
                'en' => 'Mozambique',
                'zh' => '莫桑比克',
            ], [
                'iso' => 'MM',
                'iso3' => 'MMR',
                'de' => 'Myanmar',
                'en' => 'Myanmar',
                'zh' => '缅甸',
            ], [
                'iso' => 'NR',
                'iso3' => 'NRU',
                'de' => 'Nauru',
                'en' => 'Nauru',
                'zh' => '瑙鲁',
            ], [
                'iso' => 'NP',
                'iso3' => 'NPL',
                'de' => 'Népal',
                'en' => 'Nepal',
                'zh' => '尼泊尔',
            ], [
                'iso' => 'NC',
                'iso3' => 'NCL',
                'de' => 'Neukaledonien',
                'en' => 'New Caledonia',
                'zh' => '新喀里多尼亚',
            ], [
                'iso' => 'NZ',
                'iso3' => 'NZL',
                'de' => 'Neuseeland',
                'en' => 'New Zealand',
                'zh' => '新西兰',
            ], [
                'iso' => 'NI',
                'iso3' => 'NIC',
                'de' => 'Nicaragua',
                'en' => 'Nicaragua',
                'zh' => '尼加拉瓜',
            ], [
                'iso' => 'NE',
                'iso3' => 'NER',
                'de' => 'Niger',
                'en' => 'Niger',
                'zh' => '尼日尔',
            ], [
                'iso' => 'NG',
                'iso3' => 'NGA',
                'de' => 'Nigeria',
                'en' => 'Nigeria',
                'zh' => '尼日利亚',
            ], [
                'iso' => 'NU',
                'iso3' => 'NIU',
                'de' => 'Niue',
                'en' => 'Niue',
                'zh' => '纽埃',
            ], [
                'iso' => 'NF',
                'iso3' => 'NFK',
                'de' => 'Norfolkinsel',
                'en' => 'Norfolk Island',
                'zh' => '诺福克岛',
            ], [
                'iso' => 'KP',
                'iso3' => 'PRK',
                'de' => 'Nordkorea',
                'en' => 'Korea (Democratic People\'s Republic of)',
                'zh' => '朝鲜',
            ], [
                'iso' => 'MP',
                'iso3' => 'MNP',
                'de' => 'Nördliche Marianen',
                'en' => 'Northern Mariana Islands',
                'zh' => '北马里亚纳群岛',
            ], [
                'iso' => 'OM',
                'iso3' => 'OMN',
                'de' => 'Oman',
                'en' => 'Oman',
                'zh' => '阿曼',
            ], [
                'iso' => 'PK',
                'iso3' => 'PAK',
                'de' => 'Pakistan',
                'en' => 'Pakistan',
                'zh' => '巴基斯坦',
            ], [
                'iso' => 'PW',
                'iso3' => 'PLW',
                'de' => 'Palau',
                'en' => 'Palau',
                'zh' => '帕劳',
            ], [
                'iso' => 'PS',
                'iso3' => 'PSE',
                'de' => 'Palästina',
                'en' => 'Palestine, State of',
                'zh' => '巴勒斯坦',
            ], [
                'iso' => 'PA',
                'iso3' => 'PAN',
                'de' => 'Panama',
                'en' => 'Panama',
                'zh' => '巴拿马',
            ], [
                'iso' => 'PG',
                'iso3' => 'PNG',
                'de' => 'Papua-Neuguinea',
                'en' => 'Papua New Guinea',
                'zh' => '巴布亚新几内亚',
            ], [
                'iso' => 'PY',
                'iso3' => 'PRY',
                'de' => 'Paraguay',
                'en' => 'Paraguay',
                'zh' => '巴拉圭',
            ], [
                'iso' => 'PE',
                'iso3' => 'PER',
                'de' => 'Peru',
                'en' => 'Peru',
                'zh' => '秘鲁',
            ], [
                'iso' => 'PH',
                'iso3' => 'PHL',
                'de' => 'Philippinen',
                'en' => 'Philippines',
                'zh' => '菲律宾',
            ], [
                'iso' => 'PN',
                'iso3' => 'PCN',
                'de' => 'Pitcairn',
                'en' => 'Pitcairn',
                'zh' => '皮特凯恩',
            ], [
                'iso' => 'PR',
                'iso3' => 'PRI',
                'de' => 'Puerto Rico',
                'en' => 'Puerto Rico',
                'zh' => '波多黎各',
            ], [
                'iso' => 'QA',
                'iso3' => 'QAT',
                'de' => 'Katar',
                'en' => 'Qatar',
                'zh' => '卡塔尔',
            ], [
                'iso' => 'XK',
                'iso3' => 'KOS',
                'de' => 'Republik Kosovo',
                'en' => 'Republic of Kosovo',
                'zh' => '科索沃共和国',
            ], [
                'iso' => 'RE',
                'iso3' => 'REU',
                'de' => 'Réunion',
                'en' => 'Réunion',
                'zh' => '留尼汪',
            ], [
                'iso' => 'RU',
                'iso3' => 'RUS',
                'de' => 'Russland',
                'en' => 'Russian Federation',
                'zh' => '俄罗斯联邦',
            ], [
                'iso' => 'RW',
                'iso3' => 'RWA',
                'de' => 'Ruanda',
                'en' => 'Rwanda',
                'zh' => '卢旺达',
            ], [
                'iso' => 'BL',
                'iso3' => 'BLM',
                'de' => 'Saint-Barthélemy',
                'en' => 'Saint Barthélemy',
                'zh' => '圣巴泰勒米',
            ], [
                'iso' => 'SH',
                'iso3' => 'SHN',
                'de' => 'Sankt Helena',
                'en' => 'Saint Helena, Ascension and Tristan da Cunha',
                'zh' => '圣赫勒拿、阿森松和特里斯坦-达库尼亚',
            ], [
                'iso' => 'KN',
                'iso3' => 'KNA',
                'de' => 'St. Kitts und Nevis',
                'en' => 'Saint Kitts and Nevis',
                'zh' => '圣基茨和尼维斯',
            ], [
                'iso' => 'LC',
                'iso3' => 'LCA',
                'de' => 'Saint Lucia',
                'en' => 'Saint Lucia',
                'zh' => '圣卢西亚',
            ], [
                'iso' => 'MF',
                'iso3' => 'MAF',
                'de' => 'Saint Martin',
                'en' => 'Saint Martin (French part)',
                'zh' => '圣马丁（法属）',
            ], [
                'iso' => 'PM',
                'iso3' => 'SPM',
                'de' => 'Saint-Pierre und Miquelon',
                'en' => 'Saint Pierre and Miquelon',
                'zh' => '圣皮埃尔和密克隆',
            ], [
                'iso' => 'VC',
                'iso3' => 'VCT',
                'de' => 'Saint Vincent und die Grenadinen',
                'en' => 'Saint Vincent and the Grenadines',
                'zh' => '圣文森特和格林纳丁斯',
            ], [
                'iso' => 'WS',
                'iso3' => 'WSM',
                'de' => 'Samoa',
                'en' => 'Samoa',
                'zh' => '萨摩亚',
            ], [
                'iso' => 'SM',
                'iso3' => 'SMR',
                'de' => 'San Marino',
                'en' => 'San Marino',
                'zh' => '圣马力诺',
            ], [
                'iso' => 'ST',
                'iso3' => 'STP',
                'de' => 'São Tomé und Príncipe',
                'en' => 'Sao Tome and Principe',
                'zh' => '圣多美和普林西比',
            ], [
                'iso' => 'SA',
                'iso3' => 'SAU',
                'de' => 'Saudi-Arabien',
                'en' => 'Saudi Arabia',
                'zh' => '沙特阿拉伯',
            ], [
                'iso' => 'SN',
                'iso3' => 'SEN',
                'de' => 'Senegal',
                'en' => 'Senegal',
                'zh' => '塞内加尔',
            ], [
                'iso' => 'RS',
                'iso3' => 'SRB',
                'de' => 'Serbien',
                'en' => 'Serbia',
                'zh' => '塞尔维亚',
            ], [
                'iso' => 'SC',
                'iso3' => 'SYC',
                'de' => 'Seychellen',
                'en' => 'Seychelles',
                'zh' => '塞舌尔',
            ], [
                'iso' => 'SL',
                'iso3' => 'SLE',
                'de' => 'Sierra Leone',
                'en' => 'Sierra Leone',
                'zh' => '塞拉利昂',
            ], [
                'iso' => 'SG',
                'iso3' => 'SGP',
                'de' => 'Singapur',
                'en' => 'Singapore',
                'zh' => '新加坡',
            ], [
                'iso' => 'SX',
                'iso3' => 'SXM',
                'de' => 'Sint Maarten (niederl. Teil)',
                'en' => 'Sint Maarten (Dutch part)',
                'zh' => '荷属圣马丁',
            ], [
                'iso' => 'SB',
                'iso3' => 'SLB',
                'de' => 'Salomonen',
                'en' => 'Solomon Islands',
                'zh' => '所罗门群岛',
            ], [
                'iso' => 'SO',
                'iso3' => 'SOM',
                'de' => 'Somalia',
                'en' => 'Somalia',
                'zh' => '索马里',
            ], [
                'iso' => 'ZA',
                'iso3' => 'ZAF',
                'de' => 'Republik Südafrika',
                'en' => 'South Africa',
                'zh' => '南非共和国',
            ], [
                'iso' => 'GS',
                'iso3' => 'SGS',
                'de' => 'Südgeorgien und die Südlichen Sandwichinseln',
                'en' => 'South Georgia and the South Sandwich Islands',
                'zh' => '南乔治亚和南桑威奇群岛',
            ], [
                'iso' => 'KR',
                'iso3' => 'KOR',
                'de' => 'Südkorea',
                'en' => 'Korea (Republic of)',
                'zh' => '韩国',
            ], [
                'iso' => 'SS',
                'iso3' => 'SSD',
                'de' => 'Südsudan',
                'en' => 'South Sudan',
                'zh' => '南苏丹',
            ], [
                'iso' => 'LK',
                'iso3' => 'LKA',
                'de' => 'Sri Lanka',
                'en' => 'Sri Lanka',
                'zh' => '斯里兰卡',
            ], [
                'iso' => 'SD',
                'iso3' => 'SDN',
                'de' => 'Sudan',
                'en' => 'Sudan',
                'zh' => '苏丹',
            ], [
                'iso' => 'SR',
                'iso3' => 'SUR',
                'de' => 'Suriname',
                'en' => 'Suriname',
                'zh' => '苏里南',
            ], [
                'iso' => 'SJ',
                'iso3' => 'SJM',
                'de' => 'Svalbard und Jan Mayen',
                'en' => 'Svalbard and Jan Mayen',
                'zh' => '斯瓦尔巴特和扬马延',
            ], [
                'iso' => 'SZ',
                'iso3' => 'SWZ',
                'de' => 'Swasiland',
                'en' => 'Swaziland',
                'zh' => '斯威士兰',
            ], [
                'iso' => 'SY',
                'iso3' => 'SYR',
                'de' => 'Syrien',
                'en' => 'Syrian Arab Republic',
                'zh' => '叙利亚',
            ], [
                'iso' => 'TW',
                'iso3' => 'TWN',
                'de' => 'Taiwan',
                'en' => 'Taiwan',
                'zh' => '台湾',
            ], [
                'iso' => 'TJ',
                'iso3' => 'TJK',
                'de' => 'Tadschikistan',
                'en' => 'Tajikistan',
                'zh' => '塔吉克斯坦',
            ], [
                'iso' => 'TZ',
                'iso3' => 'TZA',
                'de' => 'Tansania',
                'en' => 'Tanzania, United Republic of',
                'zh' => '坦桑尼亚',
            ], [
                'iso' => 'TH',
                'iso3' => 'THA',
                'de' => 'Thailand',
                'en' => 'Thailand',
                'zh' => '泰国',
            ], [
                'iso' => 'TL',
                'iso3' => 'TLS',
                'de' => 'Timor-Leste',
                'en' => 'Timor-Leste',
                'zh' => '东帝汶',
            ], [
                'iso' => 'TG',
                'iso3' => 'TGO',
                'de' => 'Togo',
                'en' => 'Togo',
                'zh' => '多哥',
            ], [
                'iso' => 'TK',
                'iso3' => 'TKL',
                'de' => 'Tokelau',
                'en' => 'Tokelau',
                'zh' => '托克劳',
            ], [
                'iso' => 'TO',
                'iso3' => 'TON',
                'de' => 'Tonga',
                'en' => 'Tonga',
                'zh' => '汤加',
            ], [
                'iso' => 'TT',
                'iso3' => 'TTO',
                'de' => 'Trinidad und Tobago',
                'en' => 'Trinidad and Tobago',
                'zh' => '特立尼达和多巴哥',
            ], [
                'iso' => 'TN',
                'iso3' => 'TUN',
                'de' => 'Tunesien',
                'en' => 'Tunisia',
                'zh' => '突尼斯',
            ], [
                'iso' => 'TM',
                'iso3' => 'TKM',
                'de' => 'Turkmenistan',
                'en' => 'Turkmenistan',
                'zh' => '土库曼斯坦',
            ], [
                'iso' => 'TC',
                'iso3' => 'TCA',
                'de' => 'Turks- und Caicosinseln',
                'en' => 'Turks and Caicos Islands',
                'zh' => '特克斯和凯科斯群岛',
            ], [
                'iso' => 'TV',
                'iso3' => 'TUV',
                'de' => 'Tuvalu',
                'en' => 'Tuvalu',
                'zh' => '图瓦卢',
            ], [
                'iso' => 'UG',
                'iso3' => 'UGA',
                'de' => 'Uganda',
                'en' => 'Uganda',
                'zh' => '乌干达',
            ], [
                'iso' => 'UA',
                'iso3' => 'UKR',
                'de' => 'Ukraine',
                'en' => 'Ukraine',
                'zh' => '乌克兰',
            ], [
                'iso' => 'UY',
                'iso3' => 'URY',
                'de' => 'Uruguay',
                'en' => 'Uruguay',
                'zh' => '乌拉圭',
            ], [
                'iso' => 'UZ',
                'iso3' => 'UZB',
                'de' => 'Usbekistan',
                'en' => 'Uzbekistan',
                'zh' => '乌兹别克斯坦',
            ], [
                'iso' => 'VU',
                'iso3' => 'VUT',
                'de' => 'Vanuatu',
                'en' => 'Vanuatu',
                'zh' => '瓦努阿图',
            ], [
                'iso' => 'VE',
                'iso3' => 'VEN',
                'de' => 'Venezuela',
                'en' => 'Venezuela (Bolivarian Republic of)',
                'zh' => '委内瑞拉',
            ], [
                'iso' => 'VN',
                'iso3' => 'VNM',
                'de' => 'Vietnam',
                'en' => 'Viet Nam',
                'zh' => '越南',
            ], [
                'iso' => 'WF',
                'iso3' => 'WLF',
                'de' => 'Wallis und Futuna',
                'en' => 'Wallis and Futuna',
                'zh' => '瓦利斯和富图纳',
            ], [
                'iso' => 'EH',
                'iso3' => 'ESH',
                'de' => 'Westsahara',
                'en' => 'Western Sahara',
                'zh' => '西撒哈拉',
            ], [
                'iso' => 'YE',
                'iso3' => 'YEM',
                'de' => 'Jemen',
                'en' => 'Yemen',
                'zh' => '也门',
            ], [
                'iso' => 'ZM',
                'iso3' => 'ZMB',
                'de' => 'Sambia',
                'en' => 'Zambia',
                'zh' => '赞比亚',
            ], [
                'iso' => 'ZW',
                'iso3' => 'ZWE',
                'de' => 'Simbabwe',
                'en' => 'Zimbabwe',
                'zh' => '津巴布韦',
            ],
        ];
    }
}
