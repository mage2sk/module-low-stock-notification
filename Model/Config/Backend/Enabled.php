<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class Enabled extends Value
{
    public function beforeSave()
    {
        return parent::beforeSave();
    }
}
