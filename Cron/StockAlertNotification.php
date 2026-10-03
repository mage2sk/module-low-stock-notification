<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;
use Panth\LowStockNotification\Model\EmailSender;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Psr\Log\LoggerInterface;

class StockAlertNotification
{
    private const BATCH_SIZE = 200;

    private CollectionFactory $stockAlertCollectionFactory;

    private ProductRepositoryInterface $productRepository;

    private EmailSender $emailSender;

    private StockAlertHelper $helper;

    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $stockAlertCollectionFactory,
        ProductRepositoryInterface $productRepository,
        EmailSender $emailSender,
        StockAlertHelper $helper,
        LoggerInterface $logger
    ) {
        $this->stockAlertCollectionFactory = $stockAlertCollectionFactory;
        $this->productRepository = $productRepository;
        $this->emailSender = $emailSender;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $lastId = 0;

        do {
            $collection = $this->stockAlertCollectionFactory->create()
                ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE)
                ->addFieldToFilter('alert_id', ['gt' => $lastId])
                ->setOrder('alert_id', 'ASC')
                ->setPageSize(self::BATCH_SIZE)
                ->setCurPage(1);

            $count = 0;
            foreach ($collection as $alert) {
                $count++;
                $lastId = (int) $alert->getId();
                $this->processAlert($alert);
            }
        } while ($count === self::BATCH_SIZE);
    }

    private function processAlert(StockAlert $alert): void
    {
        try {
            $storeId = (int) $alert->getStoreId();
            if (!$this->helper->isEnabled($storeId)) {
                return;
            }

            $product = $this->productRepository->getById((int) $alert->getProductId(), false, $storeId);
            if (!$product->isSalable()) {
                return;
            }

            $this->emailSender->sendAlertEmail($alert, $product);
        } catch (\Exception $e) {
            $this->logger->error('Stock alert error (alert ' . $alert->getId() . '): ' . $e->getMessage());
        }
    }
}
