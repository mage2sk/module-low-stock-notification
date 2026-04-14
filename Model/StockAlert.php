<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Stock Alert Model
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Model;

use Magento\Framework\Model\AbstractModel;

class StockAlert extends AbstractModel
{
    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 1;
    public const STATUS_SENT = 2;
    public const STATUS_CANCELLED = 3;

    /**
     * Cache tag
     */
    public const CACHE_TAG = 'panth_stock_alert';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'panth_stock_alert';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\LowStockNotification\Model\ResourceModel\StockAlert::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get alert ID
     *
     * @return int|null
     */
    public function getAlertId()
    {
        return $this->getData('alert_id');
    }

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Get product ID
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->getData('product_id');
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * Get customer name
     *
     * @return string|null
     */
    public function getCustomerName()
    {
        return $this->getData('customer_name');
    }

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Get sent at
     *
     * @return string|null
     */
    public function getSentAt()
    {
        return $this->getData('sent_at');
    }

    /**
     * Set alert ID
     *
     * @param int $alertId
     * @return $this
     */
    public function setAlertId($alertId)
    {
        return $this->setData('alert_id', $alertId);
    }

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Set product ID
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        return $this->setData('product_id', $productId);
    }

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setData('email', $email);
    }

    /**
     * Set customer name
     *
     * @param string|null $customerName
     * @return $this
     */
    public function setCustomerName($customerName)
    {
        return $this->setData('customer_name', $customerName);
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Set sent at
     *
     * @param string|null $sentAt
     * @return $this
     */
    public function setSentAt($sentAt)
    {
        return $this->setData('sent_at', $sentAt);
    }
}
