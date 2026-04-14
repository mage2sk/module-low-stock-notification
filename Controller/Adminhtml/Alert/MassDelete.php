<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert Mass Delete Controller
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;

class MassDelete extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::alert_delete';

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var StockAlertResource
     */
    private StockAlertResource $stockAlertResource;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param StockAlertResource $stockAlertResource
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        StockAlertResource $stockAlertResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->stockAlertResource = $stockAlertResource;
    }

    /**
     * Execute mass delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $alert) {
            $this->stockAlertResource->delete($alert);
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been deleted.', $collectionSize)
        );

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}
