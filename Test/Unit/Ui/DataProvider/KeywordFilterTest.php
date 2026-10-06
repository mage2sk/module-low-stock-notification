<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\Grid\Collection as GridCollection;
use Panth\LowStockNotification\Ui\DataProvider\KeywordFilter;
use PHPUnit\Framework\TestCase;

class KeywordFilterTest extends TestCase
{
    private function filter($value): Filter
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('getValue')->willReturn($value);
        return $filter;
    }

    public function testGridCollectionReceivesKeywordAsString(): void
    {
        $collection = $this->createMock(GridCollection::class);
        $collection->expects($this->once())->method('applyKeywordSearch')->with('42');

        (new KeywordFilter())->apply($collection, $this->filter(42));
    }

    public function testNullKeywordBecomesEmptyString(): void
    {
        $collection = $this->createMock(GridCollection::class);
        $collection->expects($this->once())->method('applyKeywordSearch')->with('');

        (new KeywordFilter())->apply($collection, $this->filter(null));
    }

    public function testOtherCollectionsAreLeftUntouched(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method($this->anything());

        (new KeywordFilter())->apply($collection, $this->filter('shoe'));
    }
}
