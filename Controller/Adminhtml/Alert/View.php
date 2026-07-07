<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;

class View extends Action
{
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::alert_view';

    private PageFactory $resultPageFactory;

    private StockAlertFactory $stockAlertFactory;

    private StockAlertResource $stockAlertResource;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StockAlertFactory $stockAlertFactory,
        StockAlertResource $stockAlertResource
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->stockAlertResource = $stockAlertResource;
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('alert_id');

        if ($id) {
            $model = $this->stockAlertFactory->create();
            $this->stockAlertResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This alert no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_LowStockNotification::alerts');
        $resultPage->getConfig()->getTitle()->prepend(__('View Alert #%1', $id));

        return $resultPage;
    }
}
