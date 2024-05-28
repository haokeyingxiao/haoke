<?php declare(strict_types=1);

namespace Shopware\Elasticsearch\Admin\Indexer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('system-settings')]
final class CustomerAdminSearchIndexer extends AbstractAdminIndexer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly IteratorFactory $factory,
        private readonly EntityRepository $repository,
        private readonly int $indexingBatchSize
    ) {
    }

    public function getDecorated(): AbstractAdminIndexer
    {
        throw new DecorationPatternException(self::class);
    }

    public function getEntity(): string
    {
        return CustomerDefinition::ENTITY_NAME;
    }

    public function getName(): string
    {
        return 'customer-listing';
    }

    public function getIterator(): IterableQuery
    {
        return $this->factory->createIterator($this->getEntity(), null, $this->indexingBatchSize);
    }

    public function globalData(array $result, Context $context): array
    {
        $ids = array_column($result['hits'], 'id');

        return [
            'total' => (int) $result['total'],
            'data' => $this->repository->search(new Criteria($ids), $context)->getEntities(),
        ];
    }

    public function fetch(array $ids): array
    {
        $data = $this->connection->fetchAllAssociative(
            '
            SELECT LOWER(HEX(customer.id)) as id,
                   GROUP_CONCAT(DISTINCT tag.name SEPARATOR " ") as tags,
                   GROUP_CONCAT(DISTINCT country_translation.name SEPARATOR " ") as country,
                   GROUP_CONCAT(DISTINCT customer_address.first_name SEPARATOR " ") as address_first_name,
                   GROUP_CONCAT(DISTINCT customer_address.last_name SEPARATOR " ") as address_last_name,
                   GROUP_CONCAT(DISTINCT customer_address.company SEPARATOR " ") as address_company,
                   GROUP_CONCAT(DISTINCT customer_address.city SEPARATOR " ") as city,
                   GROUP_CONCAT(DISTINCT customer_address.street SEPARATOR " ") as street,
                   GROUP_CONCAT(DISTINCT customer_address.zipcode SEPARATOR " ") as zipcode,
                   GROUP_CONCAT(DISTINCT customer_address.phone_number SEPARATOR " ") as phone_number,
                   GROUP_CONCAT(DISTINCT customer_address.additional_address_line1 SEPARATOR " ") as additional_address_line1,
                   GROUP_CONCAT(DISTINCT customer_address.additional_address_line2 SEPARATOR " ") as additional_address_line2,
                   customer.first_name,
                   customer.last_name,
                   customer.email,
                   customer.company,
                   customer.customer_number
            FROM customer
                LEFT JOIN customer_address
                    ON customer_address.customer_id = customer.id
                LEFT JOIN country
                    ON customer_address.country_id = country.id
                LEFT JOIN country_translation
                    ON country.id = country_translation.country_id
                LEFT JOIN customer_tag
                    ON customer.id = customer_tag.customer_id
                LEFT JOIN tag
                    ON customer_tag.tag_id = tag.id
            WHERE customer.id IN (:ids)
            GROUP BY customer.id
        ',
            [
                'ids' => Uuid::fromHexToBytesList($ids),
            ],
            [
                'ids' => ArrayParameterType::BINARY,
            ]
        );

        $mapped = [];
        foreach ($data as $row) {
            $id = (string) $row['id'];
            $text = \implode(' ', array_filter(array_unique(array_values($row))));
            $mapped[$id] = ['id' => $id, 'text' => \strtolower($text)];
        }

        return $mapped;
    }
}
