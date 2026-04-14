<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Stock Alert Collection
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\ResourceModel\StockAlert;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'alert_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Panth\LowStockNotification\Model\StockAlert::class,
            \Panth\LowStockNotification\Model\ResourceModel\StockAlert::class
        );
    }
}
