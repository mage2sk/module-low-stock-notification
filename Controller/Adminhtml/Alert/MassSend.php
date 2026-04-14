<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert Mass Send Email Controller
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Psr\Log\LoggerInterface;

class MassSend extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::alert_send';

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

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
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param StockAlertResource $stockAlertResource
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StockAlertResource $stockAlertResource,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->stockAlertResource = $stockAlertResource;
        $this->dateTime = $dateTime;
    }

    /**
     * Execute mass send action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $sentCount = 0;
        $errorCount = 0;

        foreach ($collection as $alert) {
            try {
                $product = $this->productRepository->getById($alert->getProductId());
                $this->sendBackInStockEmail($alert, $product);

                $alert->setStatus(StockAlert::STATUS_SENT);
                $alert->setSentAt($this->dateTime->gmtDate());
                $this->stockAlertResource->save($alert);

                $sentCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to send alert email: ' . $e->getMessage());
                $errorCount++;
            }
        }

        if ($sentCount) {
            $this->messageManager->addSuccessMessage(__('A total of %1 email(s) have been sent.', $sentCount));
        }

        if ($errorCount) {
            $this->messageManager->addErrorMessage(__('Failed to send %1 email(s).', $errorCount));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
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
    }
}
