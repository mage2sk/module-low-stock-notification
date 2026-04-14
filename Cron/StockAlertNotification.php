<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Stock Alert Notification Cron
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Psr\Log\LoggerInterface;

class StockAlertNotification
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $stockAlertCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var StockAlertResource
     */
    private StockAlertResource $stockAlertResource;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @param CollectionFactory $stockAlertCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param StockAlertResource $stockAlertResource
     * @param DateTime $dateTime
     */
    public function __construct(
        CollectionFactory $stockAlertCollectionFactory,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StockAlertResource $stockAlertResource,
        DateTime $dateTime
    ) {
        $this->stockAlertCollectionFactory = $stockAlertCollectionFactory;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->stockAlertResource = $stockAlertResource;
        $this->dateTime = $dateTime;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $collection = $this->stockAlertCollectionFactory->create()
            ->addFieldToFilter('status', StockAlert::STATUS_ACTIVE);

        foreach ($collection as $alert) {
            try {
                $product = $this->productRepository->getById($alert->getProductId());
                $stockItem = $this->stockRegistry->getStockItem($alert->getProductId());

                if ($stockItem->getIsInStock() && $stockItem->getQty() > 0) {
                    $this->sendBackInStockEmail($alert, $product);

                    $alert->setStatus(StockAlert::STATUS_SENT);
                    $alert->setSentAt($this->dateTime->gmtDate());
                    $this->stockAlertResource->save($alert);

                    $this->logger->info(
                        'Stock alert sent for product ' . $product->getId() . ' to ' . $alert->getEmail()
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Stock alert error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send back in stock email
     *
     * @param StockAlert $alert
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return void
     */
    private function sendBackInStockEmail(StockAlert $alert, $product): void
    {
        try {
            $store = $this->storeManager->getStore($alert->getStoreId());

            $emailSender = $this->scopeConfig->getValue(
                'lowstocknotification/email/sender',
                ScopeInterface::SCOPE_STORE,
                $store->getId()
            ) ?: 'general';

            $customerName = $alert->getCustomerName() ?: 'Valued Customer';

            $templateVars = [
                'customer_name' => $customerName,
                'product_name' => $product->getName(),
                'product_url' => $product->getProductUrl(),
                'product_price' => $product->getPrice(),
                'store' => $store
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('lowstocknotification_email_email_template')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($emailSender)
                ->addTo($alert->getEmail())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send stock alert email: ' . $e->getMessage());
        }
    }
}
