<?php
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

    public function getTotalAlertsCount()
    {
        return $this->collectionFactory->create()->getSize();
    }

    public function getPendingAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE)
            ->getSize();
    }

    public function getSentAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_SENT)
            ->getSize();
    }

    public function getCancelledAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_CANCELLED)
            ->getSize();
    }

    public function getRecentAlerts($limit = 10)
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'DESC')
            ->setPageSize($limit);
    }

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

    public function getViewAlertUrl($alertId)
    {
        return $this->getUrl('lowstocknotification/alert/view', ['alert_id' => $alertId]);
    }

    public function getManageAlertsUrl()
    {
        return $this->getUrl('lowstocknotification/alert/index');
    }

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

    public function getTodayAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }

    public function getTodaySentCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_SENT)
            ->addFieldToFilter('sent_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }
}
