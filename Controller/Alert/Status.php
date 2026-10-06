<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Alert;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;

class Status implements HttpGetActionInterface
{
    private JsonFactory $resultJsonFactory;

    private CustomerSession $customerSession;

    private StockAlertFactory $stockAlertFactory;

    private StockAlertHelper $helper;

    private RequestInterface $request;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StockAlertFactory $stockAlertFactory,
        StockAlertHelper $helper,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->helper = $helper;
        $this->request = $request;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);

        if (!$this->helper->isStockAlertEnabled()) {
            return $result->setData(['subscribed' => false]);
        }

        $productId = (int) $this->request->getParam('product_id');
        if (!$productId) {
            return $result->setData(['subscribed' => false]);
        }

        $email = '';
        $guestAlertIds = [];
        if ($this->customerSession->isLoggedIn()) {
            $email = (string) $this->customerSession->getCustomer()->getEmail();
        } else {
            $guestAlertIds = array_values(array_filter(array_map(
                'intval',
                (array) $this->customerSession->getData(Stock::SESSION_ALERT_IDS)
            )));
        }

        if ($email === '' && !$guestAlertIds) {
            return $result->setData(['subscribed' => false]);
        }

        try {
            $collection = $this->stockAlertFactory->create()->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);
            if ($guestAlertIds) {
                $collection->addFieldToFilter('alert_id', ['in' => $guestAlertIds]);
            } else {
                $collection->addFieldToFilter('email', $email);
            }

            return $result->setData(['subscribed' => $collection->getSize() > 0]);
        } catch (\Exception $e) {
            return $result->setData(['subscribed' => false]);
        }
    }
}
