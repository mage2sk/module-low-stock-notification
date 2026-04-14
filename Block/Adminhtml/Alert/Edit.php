<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert Edit Block
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Block\Adminhtml\Alert;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;

class Edit extends Template
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var StockAlertFactory
     */
    private StockAlertFactory $stockAlertFactory;

    /**
     * @var StockAlertResource
     */
    private StockAlertResource $stockAlertResource;

    /**
     * @var StockAlert|null
     */
    private ?StockAlert $alert = null;

    /**
     * @var string
     */
    protected $_template = 'Panth_LowStockNotification::alert/view.phtml';

    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param StockAlertFactory $stockAlertFactory
     * @param StockAlertResource $stockAlertResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        PriceCurrencyInterface $priceCurrency,
        StockAlertFactory $stockAlertFactory,
        StockAlertResource $stockAlertResource,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->priceCurrency = $priceCurrency;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->stockAlertResource = $stockAlertResource;
        parent::__construct($context, $data);
    }

    /**
     * Get current alert
     *
     * @return StockAlert
     */
    public function getAlert(): StockAlert
    {
        if ($this->alert === null) {
            $this->alert = $this->stockAlertFactory->create();
            $alertId = (int) $this->getRequest()->getParam('alert_id');
            if ($alertId) {
                $this->stockAlertResource->load($this->alert, $alertId);
            }
        }
        return $this->alert;
    }

    /**
     * Get product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        try {
            return $this->productRepository->getById($this->getAlert()->getProductId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        try {
            if ($this->getAlert()->getCustomerId()) {
                return $this->customerRepository->getById($this->getAlert()->getCustomerId());
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * Get status label
     *
     * @param int $status
     * @return \Magento\Framework\Phrase
     */
    public function getStatusLabel($status)
    {
        switch ((int) $status) {
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
     * Get delete URL
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['alert_id' => $this->getAlert()->getId()]);
    }

    /**
     * Get send URL
     *
     * @return string
     */
    public function getSendUrl(): string
    {
        return $this->getUrl('*/*/send', ['alert_id' => $this->getAlert()->getId()]);
    }

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/index');
    }

    /**
     * Can send email
     *
     * @return bool
     */
    public function canSendEmail(): bool
    {
        return (int) $this->getAlert()->getStatus() === StockAlert::STATUS_ACTIVE;
    }

    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price): string
    {
        return $this->priceCurrency->format($price, false);
    }

    /**
     * Get status class
     *
     * @param int $status
     * @return string
     */
    public function getStatusClass($status): string
    {
        switch ((int) $status) {
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
}
