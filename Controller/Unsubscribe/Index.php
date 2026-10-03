<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Unsubscribe;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Model\RateLimiter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface
{
    private RequestInterface $request;
    private RedirectFactory $redirectFactory;
    private ManagerInterface $messageManager;
    private CollectionFactory $collectionFactory;
    private StockAlertResource $stockAlertResource;
    private RateLimiter $rateLimiter;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        CollectionFactory $collectionFactory,
        StockAlertResource $stockAlertResource,
        RateLimiter $rateLimiter,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->collectionFactory = $collectionFactory;
        $this->stockAlertResource = $stockAlertResource;
        $this->rateLimiter = $rateLimiter;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $redirect->setUrl($this->storeManager->getStore()->getBaseUrl());

        if (!$this->rateLimiter->isAllowed('unsubscribe_link')) {
            $this->messageManager->addErrorMessage(__('Too many requests. Please try again later.'));
            return $redirect;
        }

        $alertId = (int) $this->request->getParam('id');
        $token = (string) $this->request->getParam('token');

        $alert = null;
        if ($alertId > 0 && $token !== '') {
            $alert = $this->collectionFactory->create()
                ->addFieldToFilter('alert_id', $alertId)
                ->setPageSize(1)
                ->getFirstItem();
        }

        $storedToken = $alert ? (string) $alert->getData('unsubscribe_token') : '';
        if (!$alert || !$alert->getId() || $storedToken === '' || !hash_equals($storedToken, $token)) {
            $this->messageManager->addErrorMessage(__('This unsubscribe link is invalid or has expired.'));
            return $redirect;
        }

        try {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('email', (string) $alert->getEmail())
                ->addFieldToFilter('store_id', (int) $alert->getStoreId())
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);
            foreach ($collection as $activeAlert) {
                $activeAlert->setStatus(StockAlert::STATUS_CANCELLED);
                $this->stockAlertResource->save($activeAlert);
            }
            $this->messageManager->addSuccessMessage(
                __('You have been unsubscribed from back-in-stock alerts.')
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'LowStockNotification: token unsubscribe failed for alert #' . $alertId . ': ' . $e->getMessage()
            );
            $this->messageManager->addErrorMessage(__('Unable to update your subscription. Please try again later.'));
        }

        return $redirect;
    }
}
