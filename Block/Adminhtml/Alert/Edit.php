<?php
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
    private ProductRepositoryInterface $productRepository;

    private CustomerRepositoryInterface $customerRepository;

    private PriceCurrencyInterface $priceCurrency;

    private StockAlertFactory $stockAlertFactory;

    private StockAlertResource $stockAlertResource;

    private ?StockAlert $alert = null;

    protected $_template = 'Panth_LowStockNotification::alert/view.phtml';

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

    public function getProduct()
    {
        try {
            return $this->productRepository->getById($this->getAlert()->getProductId());
        } catch (\Exception $e) {
            return null;
        }
    }

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

    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['alert_id' => $this->getAlert()->getId()]);
    }

    public function getSendUrl(): string
    {
        return $this->getUrl('*/*/send', ['alert_id' => $this->getAlert()->getId()]);
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/index');
    }

    public function canSendEmail(): bool
    {
        return (int) $this->getAlert()->getStatus() === StockAlert::STATUS_ACTIVE;
    }

    public function formatPrice($price): string
    {
        return $this->priceCurrency->format($price, false);
    }

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
