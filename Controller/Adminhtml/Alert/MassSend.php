<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\LowStockNotification\Model\EmailSender;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Psr\Log\LoggerInterface;

class MassSend extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::alert_send';

    private Filter $filter;

    private CollectionFactory $collectionFactory;

    private EmailSender $emailSender;

    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        EmailSender $emailSender,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->emailSender = $emailSender;
        $this->logger = $logger;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $sentCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($collection as $alert) {
            if ((int) $alert->getStatus() !== StockAlert::STATUS_ACTIVE) {
                $skippedCount++;
                continue;
            }

            try {
                $this->emailSender->sendAlertEmail($alert);
                $sentCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to send alert email: ' . $e->getMessage());
                $errorCount++;
            }
        }

        if ($sentCount) {
            $this->messageManager->addSuccessMessage(__('A total of %1 email(s) have been sent.', $sentCount));
        }

        if ($skippedCount) {
            $this->messageManager->addNoticeMessage(
                __('%1 alert(s) were skipped because they are not pending.', $skippedCount)
            );
        }

        if ($errorCount) {
            $this->messageManager->addErrorMessage(__('Failed to send %1 email(s).', $errorCount));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}
