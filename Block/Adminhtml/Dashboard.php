<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;

class Dashboard extends Template
{
    protected $collectionFactory;
    private ProductRepositoryInterface $productRepository;
    private array $productNameCache = [];
    private TimezoneInterface $timezone;

    protected $_template = 'Panth_LowStockNotification::dashboard.phtml';

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        array $data = [],
        ?TimezoneInterface $timezone = null
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
        $this->timezone = $timezone ?? $this->_localeDate;
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
                return 'lsn-status is-pending';
            case StockAlert::STATUS_SENT:
                return 'lsn-status is-sent';
            case StockAlert::STATUS_CANCELLED:
                return 'lsn-status is-cancelled';
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
        $today = $this->getLocalToday();
        $firstDay = $today->modify('-6 day');

        $select = $connection->select()
            ->from(['main_table' => $collection->getMainTable()], ['created_at'])
            ->where('created_at >= ?', $this->toUtc($firstDay));

        $counts = [];
        foreach ($connection->fetchCol($select) as $createdAt) {
            $day = (new \DateTimeImmutable((string)$createdAt, new \DateTimeZone('UTC')))
                ->setTimezone($today->getTimezone())
                ->format('Y-m-d');
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->modify('-' . $i . ' day')->format('Y-m-d');
            $result[] = ['date' => $day, 'count' => $counts[$day] ?? 0];
        }
        return $result;
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
            ->addFieldToFilter('created_at', ['gteq' => $this->toUtc($this->getLocalToday())])
            ->getSize();
    }

    public function getTodaySentCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_SENT)
            ->addFieldToFilter('sent_at', ['gteq' => $this->toUtc($this->getLocalToday())])
            ->getSize();
    }

    private function getLocalToday(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->timezone->date())->setTime(0, 0);
    }

    private function toUtc(\DateTimeImmutable $localTime): string
    {
        return $localTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
