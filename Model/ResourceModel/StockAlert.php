<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Stock Alert Resource Model
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class StockAlert extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('panth_stock_alert', 'alert_id');
    }
}
