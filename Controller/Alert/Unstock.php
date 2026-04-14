<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Unsubscribe from Stock Alert Controller
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Alert;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;

class Unstock implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var StockAlertFactory
     */
    private StockAlertFactory $stockAlertFactory;

    /**
     * @var StockAlertHelper
     */
    private StockAlertHelper $helper;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var StockAlertResource
     */
    private StockAlertResource $stockAlertResource;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param StockAlertFactory $stockAlertFactory
     * @param StockAlertHelper $helper
     * @param RequestInterface $request
     * @param StockAlertResource $stockAlertResource
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        StockAlertFactory $stockAlertFactory,
        StockAlertHelper $helper,
        RequestInterface $request,
        StockAlertResource $stockAlertResource
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->helper = $helper;
        $this->request = $request;
        $this->stockAlertResource = $stockAlertResource;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isStockAlertEnabled()) {
            return $result->setData([
                'error' => true,
                'message' => __('Stock alerts are disabled.')
            ]);
        }

        $productId = (int) $this->request->getParam('product_id');
        $email = trim((string) $this->request->getParam('email'));

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        if (!$customerId && !$email) {
            return $result->setData([
                'error' => true,
                'message' => __('Email address is required.')
            ]);
        }

        if ($customerId) {
            $email = $this->customerSession->getCustomer()->getEmail();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $result->setData([
                'error' => true,
                'message' => __('Please enter a valid email address.')
            ]);
        }

        try {
            $stockAlert = $this->stockAlertFactory->create();
            $collection = $stockAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);

            if ($collection->getSize() === 0) {
                return $result->setData([
                    'error' => true,
                    'message' => __('No active stock alert found for this product.')
                ]);
            }

            foreach ($collection as $alert) {
                $this->stockAlertResource->delete($alert);
            }

            return $result->setData([
                'success' => true,
                'message' => __('Successfully unsubscribed from stock alerts for this product.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => __('Unable to unsubscribe from stock alerts. Please try again later.')
            ]);
        }
    }
}
