<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\System\Snippet\Subscriber;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\CustomFieldCollection;
use Shopware\Core\System\Snippet\Aggregate\SnippetSet\SnippetSetCollection;

/**
 * @internal
 */
#[Package('services-settings')]
class CustomFieldSubscriberTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<CustomFieldSetCollection>
     */
    private EntityRepository $customFieldSetRepository;

    /**
     * @var EntityRepository<CustomFieldCollection>
     */
    private EntityRepository $customFieldRepository;

    private Context $context;

    private Connection $connection;

    /**
     * @var EntityRepository<SnippetSetCollection>
     */
    private EntityRepository $snippetSetRepository;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->customFieldSetRepository = $this->getContainer()->get('custom_field_set.repository');
        $this->customFieldRepository = $this->getContainer()->get('custom_field.repository');
        $this->snippetSetRepository = $this->getContainer()->get('snippet_set.repository');
        $this->connection = $this->getContainer()->get(Connection::class);
    }

    /**
     * @param list<string> $snippetSets
     * @param list<array{id: string, name: string, customFields: list<array{id: string, name: string, type: string, config: array{label: array<string, string>}}>}> $customFieldSets
     * @param array<string, array<string, string>> $expectedSnippets
     */
    #[DataProvider('snippetAndCustomFieldProvider')]
    public function testCustomFieldWrittenWithProvider(array $snippetSets, array $customFieldSets, array $expectedSnippets, int $expectedCount): void
    {
        foreach ($snippetSets as $set) {
            $createdSet = [
                'id' => Uuid::randomHex(),
                'name' => 'Set ' . $set,
                'baseFile' => 'de-DE',
                'iso' => $set,
            ];
            $this->snippetSetRepository->create([$createdSet], $this->context);
        }

        foreach ($customFieldSets as $customFieldSet) {
            $this->customFieldSetRepository->upsert([$customFieldSet], $this->context);
        }

        $snippets = FetchModeHelper::group(
            $this->connection->executeQuery('
                SELECT snippet_set.iso, snippet.*
                FROM snippet
                LEFT JOIN snippet_set ON snippet_set.id = snippet.snippet_set_id
            ')->fetchAllAssociative()
        );

        $snippetCount = $this->connection->executeQuery('SELECT count(*) FROM snippet')->fetchFirstColumn();

        static::assertSame($expectedCount, (int) $snippetCount[0]);
        foreach ($snippets as $locale => $languageSnippets) {
            foreach ($languageSnippets as $snippet) {
                static::assertSame($expectedSnippets[$locale][$snippet['translation_key']], $snippet['value']);
            }
        }
    }

    public static function snippetAndCustomFieldProvider(): \Generator
    {
        $customFieldSet = Uuid::randomHex();
        $customField = Uuid::randomHex();

        yield 'With fitting labels' => [
            'snippetSets' => [
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label',
                                    'en-GB' => 'EN - Label',
                                    'zh-CN' => 'ZH - Label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'EN - Label',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'ZH - Label',
                ],
            ],
            'expectedCount' => 3,
        ];

        yield 'One SnippetSet not used in CustomField label' => [
            'snippetSets' => [
                'fr-FR',
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label',
                                    'en-GB' => 'EN - Label',
                                    'zh-CN' => 'ZH - Label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'EN - Label',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'ZH - Label',
                ],
                'fr-FR' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
            ],
            'expectedCount' => 4,
        ];

        yield 'One SnippetSet is not available' => [
            'snippetSets' => [
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label',
                                    'en-GB' => 'EN - Label',
                                    'zh-CN' => 'ZH - Label',
                                    'fr-FR' => 'FR - Label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'EN - Label',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'ZH - Label',
                ],
            ],
            'expectedCount' => 3,
        ];

        yield 'Multiple SnippetSets for one iso code' => [
            'snippetSets' => [
                'de-DE',
                'en-GB',
                'zh-CN',
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label',
                                    'en-GB' => 'EN - Label',
                                    'zh-CN' => 'ZH - Label',
                                    'fr-FR' => 'FR - Label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'EN - Label',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'ZH - Label',
                ],
            ],
            'expectedCount' => 6,
        ];

        yield 'Create CustomField without label' => [
            'snippetSets' => [
                'de-DE',
                'en-GB',
                'fr-FR',
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [],
            'expectedCount' => 0,
        ];

        yield 'One SnippetSet is not available with multiple SnippetSets for one iso code' => [
            'snippetSets' => [
                'de-DE',
                'en-GB',
                'zh-CN',
                'fr-FR',
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'it-IT' => 'FR - Label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
                'fr-FR' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
            ],
            'expectedCount' => 7,
        ];

        yield 'Add multiple CustomFields with different iso code labels' => [
            'snippetSets' => [
                'de-DE',
                'en-GB',
                'zh-CN',
                'fr-FR',
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'it-IT' => 'IT - Label',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 2',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 2',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'fr-FR' => 'FR - Label',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 3',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 3',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 4',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 4',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'en-GB' => 'EN - Label',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 5',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 5',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'zh-CN' => 'ZH - Label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'DE - Label',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'EN - Label',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'ZH - Label',
                ],
                'fr-FR' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'FR - Label',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],
            ],
            'expectedCount' => 35,
        ];

        yield 'Update one CustomField' => [
            'snippetSets' => [
                'de-DE',
                'en-GB',
                'zh-CN',
            ],
            'customFieldSets' => [
                [
                    'id' => $customFieldSet,
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => $customField,
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 1',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => $customFieldSet,
                    'customFields' => [
                        [
                            'id' => $customField,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 2',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => $customFieldSet,
                    'customFields' => [
                        [
                            'id' => $customField,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 3',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => $customFieldSet,
                    'customFields' => [
                        [
                            'id' => $customField,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 4',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $customFieldSet,
                    'customFields' => [
                        [
                            'id' => $customField,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label - 1',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
                'fr-FR' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                ],
            ],
            'expectedCount' => 6,
        ];

        yield 'Add multiple CustomFields with one iso code label' => [
            'snippetSets' => [
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 1',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 2',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 2',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 2',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 3',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 3',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 3',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 4',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 4',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 4',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 5',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 5',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label - 1',
                    'customFields.CustomField 2' => 'DE - Label - 2',
                    'customFields.CustomField 3' => 'DE - Label - 3',
                    'customFields.CustomField 4' => 'DE - Label - 4',
                    'customFields.CustomField 5' => 'DE - Label - 5',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],
            ],
            'expectedCount' => 15,
        ];

        yield 'Add multiple CustomFields with one iso code label and multiple SnippetSets for one iso code' => [
            'snippetSets' => [
                'de-DE',
            ],
            'customFieldSets' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 1',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 1',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 2',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 2',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 2',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 3',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 3',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 3',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 4',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 4',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 4',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomFieldSet 5',
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'CustomField 5',
                            'type' => 'text',
                            'config' => [
                                'label' => [
                                    'de-DE' => 'DE - Label - 5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expectedSnippets' => [
                'de-DE' => [
                    'customFields.CustomField 1' => 'DE - Label - 1',
                    'customFields.CustomField 2' => 'DE - Label - 2',
                    'customFields.CustomField 3' => 'DE - Label - 3',
                    'customFields.CustomField 4' => 'DE - Label - 4',
                    'customFields.CustomField 5' => 'DE - Label - 5',
                ],

                'en-GB' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],
                'zh-CN' => [
                    'customFields.CustomField 1' => 'CustomField 1',
                    'customFields.CustomField 2' => 'CustomField 2',
                    'customFields.CustomField 3' => 'CustomField 3',
                    'customFields.CustomField 4' => 'CustomField 4',
                    'customFields.CustomField 5' => 'CustomField 5',
                ],
            ],
            'expectedCount' => 20,
        ];
    }

    public function testSnippetIsDeletedIfCustomFieldGetsDeleted(): void
    {
        $customFieldId = Uuid::randomHex();

        $this->customFieldSetRepository->upsert([[
            'id' => Uuid::randomHex(),
            'name' => 'CustomFieldSet',
            'customFields' => [
                [
                    'id' => $customFieldId,
                    'name' => 'CustomField 1',
                    'type' => 'text',
                    'config' => [
                        'label' => [
                            'de-DE' => 'DE - Label 1',
                            'en-GB' => 'EN - Label 1',
                            'zh-CN' => 'ZH - Label 1',
                        ],
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'CustomField 2',
                    'type' => 'text',
                    'config' => [
                        'label' => [
                            'de-DE' => 'DE - Label 2',
                            'en-GB' => 'EN - Label 2',
                            'zh-CN' => 'ZH - Label 2',
                        ],
                    ],
                ],
            ],
        ]], $this->context);

        $snippets = $this->connection->executeQuery('SELECT `value` FROM `snippet` ORDER BY `value` ASC')->fetchFirstColumn();
        static::assertSame([
            'DE - Label 1',
            'DE - Label 2',
            'EN - Label 1',
            'EN - Label 2',
            'ZH - Label 1',
            'ZH - Label 2',
        ], $snippets);

        $this->customFieldRepository->delete([['id' => $customFieldId]], $this->context);

        $snippets = $this->connection->executeQuery('SELECT `value` FROM `snippet` ORDER BY `value` ASC')->fetchFirstColumn();
        static::assertSame([
            'DE - Label 2',
            'EN - Label 2',
            'ZH - Label 2',
        ], $snippets);
    }

    public function testReinsertOfCustomFieldsWorks(): void
    {
        $customFieldId = Uuid::randomHex();
        $customField = [
            'id' => $customFieldId,
            'name' => 'CustomField 1',
            'type' => 'text',
            'config' => [
                'label' => [
                    'de-DE' => 'DE - Label 1',
                    'en-GB' => 'EN - Label 1',
                    'zh-CN' => 'ZH - Label 1',
                ],
            ],
        ];

        $this->customFieldSetRepository->upsert([[
            'id' => Uuid::randomHex(),
            'name' => 'CustomFieldSet',
            'customFields' => [$customField],
        ]], $this->context);

        $this->customFieldRepository->delete([['id' => $customFieldId]], $this->context);

        $this->customFieldRepository->create([$customField], $this->context);

        $snippets = $this->connection->executeQuery('SELECT `value` FROM `snippet` ORDER BY `value` ASC')->fetchFirstColumn();
        static::assertSame([
            'DE - Label 1',
            'EN - Label 1',
            'ZH - Label 1',
        ], $snippets);
    }
}
