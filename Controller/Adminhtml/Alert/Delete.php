<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert Delete Controller
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;

class Delete extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::alert_delete';

    /**
     * @var StockAlertFactory
     */
    private StockAlertFactory $stockAlertFactory;

    /**
     * @var StockAlertResource
     */
    private StockAlertResource $stockAlertResource;

    /**
     * @param Context $context
     * @param StockAlertFactory $stockAlertFactory
     * @param StockAlertResource $stockAlertResource
     */
    public function __construct(
        Context $context,
        StockAlertFactory $stockAlertFactory,
        StockAlertResource $stockAlertResource
    ) {
        parent::__construct($context);
        $this->stockAlertFactory = $stockAlertFactory;
        $this->stockAlertResource = $stockAlertResource;
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('alert_id');

        if ($id) {
            try {
                $model = $this->stockAlertFactory->create();
                $this->stockAlertResource->load($model, $id);
                $this->stockAlertResource->delete($model);
                $this->messageManager->addSuccessMessage(__('The alert has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/view', ['alert_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find an alert to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
