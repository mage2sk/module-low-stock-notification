<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\EmailSender;

class Send extends Action
{
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::alert_send';

    private StockAlertFactory $stockAlertFactory;

    private StockAlertResource $stockAlertResource;

    private EmailSender $emailSender;

    public function __construct(
        Context $context,
        StockAlertFactory $stockAlertFactory,
        StockAlertResource $stockAlertResource,
        EmailSender $emailSender
    ) {
        parent::__construct($context);
        $this->stockAlertFactory = $stockAlertFactory;
        $this->stockAlertResource = $stockAlertResource;
        $this->emailSender = $emailSender;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('alert_id');

        if ($id) {
            try {
                $model = $this->stockAlertFactory->create();
                $this->stockAlertResource->load($model, $id);

                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This alert no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }

                $this->emailSender->sendAlertEmail($model);
                $this->messageManager->addSuccessMessage(__('The alert email has been sent.'));

                return $resultRedirect->setPath('*/*/view', ['alert_id' => $id]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/view', ['alert_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find an alert to send.'));
        return $resultRedirect->setPath('*/*/');
    }
}
