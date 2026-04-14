<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Dashboard Block
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;

class Dashboard extends Template
{
    protected $collectionFactory;
    private ProductRepositoryInterface $productRepository;
    private array $productNameCache = [];

    protected $_template = 'Panth_LowStockNotification::dashboard.phtml';

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get product name by ID (cached)
     */
    public function getProductName(int $productId): string
    {
        if (!isset($this->productNameCache[$productId])) {
            try {
                $product = $this->productRepository->getById($productId);
                $this->productNameCache[$productId] = $product->getName();
            } catch (\Exception $e) {
                $this->productNameCache[$productId] = 'Product #' . $productId;
            }
        }
        return $this->productNameCache[$productId];
    }

    /**
     * Get total alerts count
     *
     * @return int
     */
    public function getTotalAlertsCount()
    {
        return $this->collectionFactory->create()->getSize();
    }

    /**
     * Get pending alerts count
     *
     * @return int
     */
    public function getPendingAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE)
            ->getSize();
    }

    /**
     * Get sent alerts count
     *
     * @return int
     */
    public function getSentAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_SENT)
            ->getSize();
    }

    /**
     * Get cancelled alerts count
     *
     * @return int
     */
    public function getCancelledAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_CANCELLED)
            ->getSize();
    }

    /**
     * Get recent alerts
     *
     * @param int $limit
     * @return \Panth\LowStockNotification\Model\ResourceModel\StockAlert\Collection
     */
    public function getRecentAlerts($limit = 10)
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'DESC')
            ->setPageSize($limit);
    }

    /**
     * Get status label
     *
     * @param int $status
     * @return string
     */
    public function getStatusLabel($status)
    {
        switch ($status) {
            case StockAlert::STATUS_ACTIVE:
                return __('Pending');
            case StockAlert::STATUS_SENT:
                return __('Sent');
            case StockAlert::STATUS_CANCELLED:
                return __('Cancelled');
            default:
                return __('Unknown');
        }
    }

    /**
     * Get status class
     *
     * @param int $status
     * @return string
     */
    public function getStatusClass($status)
    {
        switch ($status) {
            case StockAlert::STATUS_ACTIVE:
                return 'grid-severity-notice';
            case StockAlert::STATUS_SENT:
                return 'grid-severity-minor';
            case StockAlert::STATUS_CANCELLED:
                return 'grid-severity-critical';
            default:
                return '';
        }
    }

    /**
     * Get view alert URL
     *
     * @param int $alertId
     * @return string
     */
    public function getViewAlertUrl($alertId)
    {
        return $this->getUrl('lowstocknotification/alert/view', ['alert_id' => $alertId]);
    }

    /**
     * Get manage alerts URL
     *
     * @return string
     */
    public function getManageAlertsUrl()
    {
        return $this->getUrl('lowstocknotification/alert/index');
    }

    /**
     * Get most requested products (products with most stock alerts)
     *
     * @param int $limit
     * @return array
     */
    public function getMostRequestedProducts($limit = 10)
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $collection->getMainTable()],
                [
                    'product_id',
                    'alert_count' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)')
                ]
            )
            ->where('status = ?', StockAlert::STATUS_ACTIVE)
            ->group('product_id')
            ->order('alert_count DESC')
            ->limit($limit);

        return $connection->fetchAll($select);
    }

    /**
     * Get alert trend data for the last 7 days
     *
     * @return array
     */
    public function getAlertTrendData()
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $collection->getMainTable()],
                [
                    'date' => new \Magento\Framework\DB\Sql\Expression('DATE(created_at)'),
                    'count' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)')
                ]
            )
            ->where('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->group('DATE(created_at)')
            ->order('date ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Get critical stock alerts (products with 5+ alerts)
     *
     * @return array
     */
    public function getCriticalStockAlerts()
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $collection->getMainTable()],
                [
                    'product_id',
                    'alert_count' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)')
                ]
            )
            ->where('status = ?', StockAlert::STATUS_ACTIVE)
            ->group('product_id')
            ->having('alert_count >= ?', 5)
            ->order('alert_count DESC');

        return $connection->fetchAll($select);
    }

    /**
     * Get alerts created today
     *
     * @return int
     */
    public function getTodayAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }

    /**
     * Get alerts sent today
     *
     * @return int
     */
    public function getTodaySentCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_SENT)
            ->addFieldToFilter('sent_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }
}
