<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;

class AddPlacementLayoutHandle implements ObserverInterface
{
    protected $helper;

    public function __construct(StockAlertHelper $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $layout = $observer->getData('layout');

        $fullActionName = $observer->getData('full_action_name');
        if ($fullActionName !== 'catalog_product_view') {
            return;
        }

        if (!$this->helper->isEnabled() || !$this->helper->isEnabledOnProductPage()) {
            return;
        }

        $placement = $this->helper->getPlacement();
        $layoutHandle = 'lowstocknotification_placement_' . $placement;

        $update = $layout->getUpdate();
        $update->addHandle($layoutHandle);
    }
}
