<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Email Sender Service
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Psr\Log\LoggerInterface;

class EmailSender
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param StockAlertResource $stockAlertResource
     * @param DateTime $dateTime
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StockAlertResource $stockAlertResource,
        DateTime $dateTime
    ) {
        $this->productRepository = $productRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->stockAlertResource = $stockAlertResource;
        $this->dateTime = $dateTime;
    }

    /**
     * Send alert email
     *
     * @param StockAlert $alert
     * @return bool
     * @throws \Exception
     */
    public function sendAlertEmail(StockAlert $alert): bool
    {
        try {
            $product = $this->productRepository->getById($alert->getProductId());
            $this->sendBackInStockEmail($alert, $product);

            if ((int) $alert->getStatus() !== StockAlert::STATUS_SENT) {
                $alert->setStatus(StockAlert::STATUS_SENT);
                $alert->setSentAt($this->dateTime->gmtDate());
                $this->stockAlertResource->save($alert);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send stock alert email: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send back in stock email
     *
     * @param StockAlert $alert
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return void
     * @throws \Exception
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
                'product_price' => number_format((float)$product->getFinalPrice() ?: (float)$product->getPrice(), 2),
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

            $this->logger->info(
                'Stock alert email sent for product ' . $product->getId() . ' to ' . $alert->getEmail()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to send stock alert email: ' . $e->getMessage());
            throw $e;
        }
    }
}
