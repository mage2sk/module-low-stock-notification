<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model;

use Magento\Framework\Model\AbstractModel;

class StockAlert extends AbstractModel
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_SENT = 2;
    public const STATUS_CANCELLED = 3;

    public const CACHE_TAG = 'panth_stock_alert';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_stock_alert';

    protected function _construct()
    {
        $this->_init(\Panth\LowStockNotification\Model\ResourceModel\StockAlert::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getAlertId()
    {
        return $this->getData('alert_id');
    }

    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    public function getProductId()
    {
        return $this->getData('product_id');
    }

    public function getEmail()
    {
        return $this->getData('email');
    }

    public function getCustomerName()
    {
        return $this->getData('customer_name');
    }

    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    public function getStatus()
    {
        return $this->getData('status');
    }

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function getSentAt()
    {
        return $this->getData('sent_at');
    }

    public function setAlertId($alertId)
    {
        return $this->setData('alert_id', $alertId);
    }

    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    public function setProductId($productId)
    {
        return $this->setData('product_id', $productId);
    }

    public function setEmail($email)
    {
        return $this->setData('email', $email);
    }

    public function setCustomerName($customerName)
    {
        return $this->setData('customer_name', $customerName);
    }

    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    public function setSentAt($sentAt)
    {
        return $this->setData('sent_at', $sentAt);
    }
}
