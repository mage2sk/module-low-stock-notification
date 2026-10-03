<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Url as FrontendUrl;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Psr\Log\LoggerInterface;

class EmailSender
{
    public const XML_PATH_EMAIL_SENDER = 'lowstocknotification/email/sender';
    public const XML_PATH_EMAIL_TEMPLATE = 'lowstocknotification/email/email_template';
    public const DEFAULT_TEMPLATE = 'lowstocknotification_email_email_template';

    private ProductRepositoryInterface $productRepository;

    private TransportBuilder $transportBuilder;

    private StoreManagerInterface $storeManager;

    private ScopeConfigInterface $scopeConfig;

    private LoggerInterface $logger;

    private StockAlertResource $stockAlertResource;

    private DateTime $dateTime;

    private PriceCurrencyInterface $priceCurrency;

    private FrontendUrl $frontendUrl;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StockAlertResource $stockAlertResource,
        DateTime $dateTime,
        PriceCurrencyInterface $priceCurrency,
        ?FrontendUrl $frontendUrl = null
    ) {
        $this->productRepository = $productRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->stockAlertResource = $stockAlertResource;
        $this->dateTime = $dateTime;
        $this->priceCurrency = $priceCurrency;
        $this->frontendUrl = $frontendUrl ?? ObjectManager::getInstance()->get(FrontendUrl::class);
    }

    public function sendAlertEmail(StockAlert $alert, $product = null): bool
    {
        try {
            if ($product === null) {
                $product = $this->productRepository->getById(
                    (int) $alert->getProductId(),
                    false,
                    (int) $alert->getStoreId()
                );
            }
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

    private function sendBackInStockEmail(StockAlert $alert, $product): void
    {
        $store = $this->storeManager->getStore($alert->getStoreId());

        $emailSender = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        ) ?: 'general';

        $templateId = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        ) ?: self::DEFAULT_TEMPLATE;

        $customerName = $alert->getCustomerName() ?: 'Valued Customer';

        $price = (float) $product->getFinalPrice() ?: (float) $product->getPrice();

        if ((string) $alert->getData('unsubscribe_token') === '') {
            $this->stockAlertResource->save($alert);
        }
        $unsubscribeUrl = $this->frontendUrl->setScope($store->getId())->getUrl('lowstocknotification/unsubscribe/index', [
            'id' => (int) $alert->getId(),
            'token' => (string) $alert->getData('unsubscribe_token'),
            '_nosid' => true,
        ]);

        $templateVars = [
            'customer_name' => $customerName,
            'product_name' => $product->getName(),
            'product_url' => $product->getProductUrl(),
            'product_price' => $this->priceCurrency->convertAndFormat($price, false, 2, $store),
            'unsubscribe_url' => $unsubscribeUrl,
            'store' => $store
        ];

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($templateId)
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ])
            ->setTemplateVars($templateVars)
            ->setFromByScope($emailSender, $store->getId())
            ->addTo($alert->getEmail())
            ->getTransport();

        $transport->sendMessage();

        $this->logger->info(
            'Stock alert email sent for product ' . $product->getId() . ' (alert ' . $alert->getId() . ')'
        );
    }
}
