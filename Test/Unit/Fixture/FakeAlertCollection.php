<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Fixture;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\Collection;

/**
 * In-memory alert collection that records the filters applied to it.
 */
class FakeAlertCollection extends Collection
{
    public array $filters = [];
    public array $orders = [];
    public ?int $pageSize = null;
    public ?int $curPage = null;
    private array $fakeItems;
    private ?int $fakeSize;
    private $fakeFirstItem;
    private ?AdapterInterface $fakeConnection;
    private string $fakeMainTable;

    public function __construct(
        array $items = [],
        ?int $size = null,
        $firstItem = null,
        ?AdapterInterface $connection = null,
        string $mainTable = 'panth_stock_alert'
    ) {
        $this->fakeItems = $items;
        $this->fakeSize = $size;
        $this->fakeFirstItem = $firstItem;
        $this->fakeConnection = $connection;
        $this->fakeMainTable = $mainTable;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        $this->filters[] = [$field, $condition];
        return $this;
    }

    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->orders[] = [$field, $direction];
        return $this;
    }

    public function setPageSize($size)
    {
        $this->pageSize = (int)$size;
        return $this;
    }

    public function setCurPage($page)
    {
        $this->curPage = (int)$page;
        return $this;
    }

    public function getSize()
    {
        return $this->fakeSize ?? count($this->fakeItems);
    }

    public function getFirstItem()
    {
        return $this->fakeFirstItem ?? ($this->fakeItems[0] ?? null);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->fakeItems);
    }

    public function getConnection()
    {
        return $this->fakeConnection;
    }

    public function getMainTable()
    {
        return $this->fakeMainTable;
    }

    public function filterValue(string $field)
    {
        foreach ($this->filters as [$name, $condition]) {
            if ($name === $field) {
                return $condition;
            }
        }
        return null;
    }
}
