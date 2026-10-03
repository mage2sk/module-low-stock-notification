<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\LowStockNotification\Model\StockAlert;

class Status implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => StockAlert::STATUS_ACTIVE, 'label' => __('Pending')],
            ['value' => StockAlert::STATUS_SENT, 'label' => __('Sent')],
            ['value' => StockAlert::STATUS_CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
