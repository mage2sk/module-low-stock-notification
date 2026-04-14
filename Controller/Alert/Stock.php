<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Subscribe to Stock Alert Controller
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Alert;

use Magento\Catalog\Api\ProductRepositoryInterface;
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

class Stock implements HttpPostActionInterface
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
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param StockAlertResource $stockAlertResource
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        StockAlertFactory $stockAlertFactory,
        StockAlertHelper $helper,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request,
        StockAlertResource $stockAlertResource
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
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
        $customerName = $this->request->getParam('customer_name');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        $allowGuests = $this->helper->isGuestAllowed();
        if (!$customerId && !$allowGuests) {
            return $result->setData([
                'error' => true,
                'message' => __('Please log in to subscribe to stock alerts.')
            ]);
        }

        if (!$customerId && !$email) {
            return $result->setData([
                'error' => true,
                'message' => __('Email address is required.')
            ]);
        }

        if (!$customerId && !$customerName) {
            return $result->setData([
                'error' => true,
                'message' => __('Name is required for guests.')
            ]);
        }

        if ($customerName && mb_strlen($customerName) > 255) {
            return $result->setData([
                'error' => true,
                'message' => __('Name is too long. Maximum 255 characters allowed.')
            ]);
        }

        if ($customerName) {
            $customerName = trim(strip_tags((string) $customerName));

            if (!$customerId && empty($customerName)) {
                return $result->setData([
                    'error' => true,
                    'message' => __('Please enter a valid name.')
                ]);
            }
        }

        if ($customerId) {
            $email = $this->customerSession->getCustomer()->getEmail();
            $customerName = $this->customerSession->getCustomer()->getFirstname() . ' ' .
                           $this->customerSession->getCustomer()->getLastname();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $result->setData([
                'error' => true,
                'message' => __('Please enter a valid email address.')
            ]);
        }

        try {
            try {
                $product = $this->productRepository->getById($productId);

                if ($product->isSalable()) {
                    return $result->setData([
                        'error' => true,
                        'message' => __('This product is currently in stock.')
                    ]);
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return $result->setData([
                    'error' => true,
                    'message' => __('Product not found.')
                ]);
            }

            // Check if alert already exists
            $existingAlert = $this->stockAlertFactory->create();
            $collection = $existingAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);

            if ($collection->getSize() > 0) {
                return $result->setData([
                    'error' => true,
                    'message' => __('You are already subscribed to stock alerts for this product.')
                ]);
            }

            // Create new alert
            $stockAlert = $this->stockAlertFactory->create();
            $stockAlert->setCustomerId($customerId)
                ->setProductId($productId)
                ->setEmail($email)
                ->setCustomerName($customerName)
                ->setStoreId((int) $this->storeManager->getStore()->getId())
                ->setStatus(StockAlert::STATUS_ACTIVE);
            $this->stockAlertResource->save($stockAlert);

            return $result->setData([
                'success' => true,
                'message' => __('You will be notified when this product is back in stock.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => __('Unable to subscribe to stock alerts. Please try again later.')
            ]);
        }
    }
}
