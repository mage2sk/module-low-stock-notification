<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Model\ResourceModel\StockAlert\Grid;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\Grid\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private Collection&MockObject $collection;

    private Select $mainSelect;

    private array $mainWhere = [];

    private array $subWhere = [];

    private array $subJoins = [];

    protected function setUp(): void
    {
        $this->mainWhere = [];
        $this->subWhere = [];
        $this->subJoins = [];

        $this->mainSelect = $this->createStub(Select::class);
        $this->mainSelect->method('where')->willReturnCallback(function ($cond) {
            $this->mainWhere[] = $cond;
            return $this->mainSelect;
        });

        $subSelect = $this->createStub(Select::class);
        foreach (['from', 'distinct'] as $method) {
            $subSelect->method($method)->willReturnSelf();
        }
        $subSelect->method('join')->willReturnCallback(function ($name, $cond) use ($subSelect) {
            $this->subJoins[] = $cond;
            return $subSelect;
        });
        $subSelect->method('where')->willReturnCallback(function ($cond) use ($subSelect) {
            $this->subWhere[] = $cond;
            return $subSelect;
        });
        $subSelect->method('assemble')->willReturn('SUBQUERY');

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($subSelect);
        $connection->method('fetchOne')->willReturn('73');
        $connection->method('quoteInto')->willReturnCallback(
            static fn ($text, $value) => str_replace('?', "'" . $value . "'", $text)
        );
        $connection->method('prepareSqlCondition')->willReturnCallback(
            static fn ($field, $condition) => $field . ' ' . json_encode($condition)
        );

        $metadata = $this->createStub(EntityMetadataInterface::class);
        $metadata->method('getLinkField')->willReturn('row_id');
        $metadataPool = $this->createStub(MetadataPool::class);
        $metadataPool->method('getMetadata')->willReturn($metadata);

        $this->collection = $this->createPartialMock(Collection::class, ['getConnection', 'getSelect', 'getTable']);
        $this->collection->method('getConnection')->willReturn($connection);
        $this->collection->method('getSelect')->willReturn($this->mainSelect);
        $this->collection->method('getTable')->willReturnArgument(0);

        $property = new \ReflectionProperty(Collection::class, 'metadataPool');
        $property->setValue($this->collection, $metadataPool);
    }

    public function testEmptyKeywordAddsNoCondition(): void
    {
        $this->assertSame($this->collection, $this->collection->applyKeywordSearch('   '));
        $this->assertSame([], $this->mainWhere);
    }

    public function testTextKeywordSearchesEmailCustomerNameAndProductName(): void
    {
        $this->collection->applyKeywordSearch(' guest ');

        $this->assertCount(1, $this->mainWhere);
        $where = $this->mainWhere[0];
        $this->assertStringContainsString("main_table.email LIKE '%guest%'", $where);
        $this->assertStringContainsString("main_table.customer_name LIKE '%guest%'", $where);
        $this->assertStringContainsString('main_table.product_id IN (SUBQUERY)', $where);
        $this->assertStringNotContainsString('main_table.product_id =', $where);
        $this->assertContains('cpv.value {"like":"%guest%"}', $this->subWhere);
    }

    public function testNumericKeywordAlsoMatchesProductId(): void
    {
        $this->collection->applyKeywordSearch('2159');

        $this->assertStringContainsString("main_table.product_id = '2159'", $this->mainWhere[0]);
    }

    public function testLikeWildcardsInKeywordAreEscaped(): void
    {
        $this->collection->applyKeywordSearch('50%_off');

        $this->assertStringContainsString('%50\%\_off%', $this->mainWhere[0]);
    }

    public function testProductNameFilterUsesNameAttributeSubquery(): void
    {
        $result = $this->collection->addFieldToFilter('product_name', ['like' => '%Sample%']);

        $this->assertSame($this->collection, $result);
        $this->assertSame(['main_table.product_id IN (SUBQUERY)'], $this->mainWhere);
        $this->assertContains('cpv.value {"like":"%Sample%"}', $this->subWhere);
        $this->assertContains('cpv.row_id = cpe.row_id AND cpv.attribute_id = 73', $this->subJoins);
    }
}
