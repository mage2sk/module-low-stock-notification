<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\ResourceModel\StockAlert;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'alert_id';

    protected function _construct()
    {
        $this->_init(
            \Panth\LowStockNotification\Model\StockAlert::class,
            \Panth\LowStockNotification\Model\ResourceModel\StockAlert::class
        );
    }
}
