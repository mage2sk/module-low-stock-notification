<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Alert;

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

class Unstock implements HttpPostActionInterface
{
    private JsonFactory $resultJsonFactory;

    private CustomerSession $customerSession;

    private StoreManagerInterface $storeManager;

    private StockAlertFactory $stockAlertFactory;

    private StockAlertHelper $helper;

    private RequestInterface $request;

    private StockAlertResource $stockAlertResource;

    private RateLimiter $rateLimiter;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        StockAlertFactory $stockAlertFactory,
        StockAlertHelper $helper,
        RequestInterface $request,
        StockAlertResource $stockAlertResource,
        RateLimiter $rateLimiter
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->helper = $helper;
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

        if (!$this->rateLimiter->isAllowed('unsubscribe')) {
            $result->setHttpResponseCode(429);
            return $result->setData([
                'error' => true,
                'message' => __('Too many requests. Please try again later.')
            ]);
        }

        $productId = (int) $this->request->getParam('product_id');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        $email = '';
        $guestAlertIds = [];
        if ($customerId) {
            $email = (string) $this->customerSession->getCustomer()->getEmail();
        } else {
            $guestAlertIds = array_values(array_filter(array_map(
                'intval',
                (array) $this->customerSession->getData(Stock::SESSION_ALERT_IDS)
            )));
        }

        if ($email === '' && !$guestAlertIds) {
            return $result->setData([
                'error' => true,
                'message' => __('No active stock alert found for this product.')
            ]);
        }

        try {
            $stockAlert = $this->stockAlertFactory->create();
            $collection = $stockAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);
            if ($guestAlertIds) {
                $collection->addFieldToFilter('alert_id', ['in' => $guestAlertIds]);
            } else {
                $collection->addFieldToFilter('email', $email);
            }

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
