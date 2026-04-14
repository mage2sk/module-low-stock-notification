<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Observer to Add Dynamic Placement Layout Handle
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;

class AddPlacementLayoutHandle implements ObserverInterface
{
    /**
     * @var StockAlertHelper
     */
    protected $helper;

    /**
     * @param StockAlertHelper $helper
     */
    public function __construct(StockAlertHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Add layout handle based on placement configuration
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Layout $layout */
        $layout = $observer->getData('layout');

        // Only on product view pages
        $fullActionName = $observer->getData('full_action_name');
        if ($fullActionName !== 'catalog_product_view') {
            return;
        }

        // Check if module is enabled
        if (!$this->helper->isEnabled() || !$this->helper->isEnabledOnProductPage()) {
            return;
        }

        // Get placement configuration and add corresponding layout handle
        $placement = $this->helper->getPlacement();
        $layoutHandle = 'lowstocknotification_placement_' . $placement;

        $update = $layout->getUpdate();
        $update->addHandle($layoutHandle);
    }
}
