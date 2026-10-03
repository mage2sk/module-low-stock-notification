<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class StockAlert extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_stock_alert', 'alert_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        if ((string) $object->getData('unsubscribe_token') === '') {
            $object->setData('unsubscribe_token', bin2hex(random_bytes(16)));
        }
        return parent::_beforeSave($object);
    }
}
