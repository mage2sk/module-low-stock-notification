<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Alert;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;
use Panth\LowStockNotification\Model\RateLimiter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;

class Stock implements HttpPostActionInterface
{
    public const SESSION_ALERT_IDS = 'lowstocknotification_alert_ids';

    private const MAX_FIELD_LENGTH = 255;

    private JsonFactory $resultJsonFactory;

    private CustomerSession $customerSession;

    private StoreManagerInterface $storeManager;

    private StockAlertFactory $stockAlertFactory;

    private StockAlertHelper $helper;

    private ProductRepositoryInterface $productRepository;

    private RequestInterface $request;

    private StockAlertResource $stockAlertResource;

    private RateLimiter $rateLimiter;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        StockAlertFactory $stockAlertFactory,
        StockAlertHelper $helper,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request,
        StockAlertResource $stockAlertResource,
        RateLimiter $rateLimiter
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->stockAlertResource = $stockAlertResource;
        $this->rateLimiter = $rateLimiter;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isStockAlertEnabled()) {
            return $result->setData([
                'error' => true,
                'message' => __('Stock alerts are disabled.')
            ]);
        }

        if (!$this->rateLimiter->isAllowed('subscribe')) {
            $result->setHttpResponseCode(429);
            return $result->setData([
                'error' => true,
                'message' => __('Too many requests. Please try again later.')
            ]);
        }

        $productId = (int) $this->request->getParam('product_id');
        $email = $this->getStringParam('email');
        $customerName = $this->getStringParam('customer_name');

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

        if (!$customerId && $email === '') {
            return $result->setData([
                'error' => true,
                'message' => __('Email address is required.')
            ]);
        }

        if (!$customerId && $customerName === '') {
            return $result->setData([
                'error' => true,
                'message' => __('Name is required for guests.')
            ]);
        }

        if (mb_strlen($customerName) > self::MAX_FIELD_LENGTH) {
            return $result->setData([
                'error' => true,
                'message' => __('Name is too long. Maximum 255 characters allowed.')
            ]);
        }

        if ($customerName !== '') {
            $customerName = trim(strip_tags($customerName));

            if (!$customerId && $customerName === '') {
                return $result->setData([
                    'error' => true,
                    'message' => __('Please enter a valid name.')
                ]);
            }
        }

        if ($customerId) {
            $email = (string) $this->customerSession->getCustomer()->getEmail();
            $customerName = $this->customerSession->getCustomer()->getFirstname() . ' ' .
                           $this->customerSession->getCustomer()->getLastname();
        }

        if (mb_strlen($email) > self::MAX_FIELD_LENGTH || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $result->setData([
                'error' => true,
                'message' => __('Please enter a valid email address.')
            ]);
        }

        try {
            $store = $this->storeManager->getStore();
            $storeId = (int) $store->getId();

            try {
                $product = $this->productRepository->getById($productId, false, $storeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $product = null;
            }

            if ($product === null
                || (int) $product->getStatus() !== ProductStatus::STATUS_ENABLED
                || !in_array((int) $store->getWebsiteId(), array_map('intval', (array) $product->getWebsiteIds()), true)
            ) {
                return $result->setData([
                    'error' => true,
                    'message' => __('Product not found.')
                ]);
            }

            if ($product->isSalable()) {
                return $result->setData([
                    'error' => true,
                    'message' => __('This product is currently in stock.')
                ]);
            }

            $existingAlert = $this->stockAlertFactory->create();
            $collection = $existingAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);

            if ($collection->getSize() > 0) {
                if (!$customerId) {
                    return $result->setData([
                        'success' => true,
                        'message' => __('You will be notified when this product is back in stock.')
                    ]);
                }
                return $result->setData([
                    'error' => true,
                    'message' => __('You are already subscribed to stock alerts for this product.')
                ]);
            }

            $stockAlert = $this->stockAlertFactory->create();
            $stockAlert->setCustomerId($customerId)
                ->setProductId($productId)
                ->setEmail($email)
                ->setCustomerName($customerName)
                ->setStoreId($storeId)
                ->setStatus(StockAlert::STATUS_ACTIVE);
            $this->stockAlertResource->save($stockAlert);

            if (!$customerId) {
                $alertIds = (array) $this->customerSession->getData(self::SESSION_ALERT_IDS);
                $alertIds[] = (int) $stockAlert->getId();
                $this->customerSession->setData(
                    self::SESSION_ALERT_IDS,
                    array_values(array_unique(array_map('intval', $alertIds)))
                );
            }

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

    private function getStringParam(string $name): string
    {
        $value = $this->request->getParam($name);
        return is_string($value) ? trim($value) : '';
    }
}
