<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class StockAlert extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_stock_alert', 'alert_id');
    }
}
