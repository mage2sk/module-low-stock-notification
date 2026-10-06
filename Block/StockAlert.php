<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;

class StockAlert extends Template
{
    private StockAlertHelper $helper;

    private CustomerSession $customerSession;

    private ProductRepositoryInterface $productRepository;

    private FormKey $formKeyService;

    private $currentProduct = null;

    public function __construct(
        Context $context,
        StockAlertHelper $helper,
        CustomerSession $customerSession,
        ProductRepositoryInterface $productRepository,
        FormKey $formKeyService,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->formKeyService = $formKeyService;
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->helper->isStockAlertEnabled();
    }

    public function isCompact(): bool
    {
        return $this->helper->isCompactStyle();
    }

    public function getProduct()
    {
        if ($this->currentProduct === null) {
            if ($this->hasData('product')) {
                $this->currentProduct = $this->getData('product');
            } else {
                $productId = (int) $this->getRequest()->getParam('id');
                if ($productId) {
                    try {
                        $this->currentProduct = $this->productRepository->getById($productId);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $this->currentProduct = false;
                    }
                } else {
                    $this->currentProduct = false;
                }
            }
        }

        return $this->currentProduct ?: null;
    }

    public function isProductOutOfStock(): bool
    {
        $product = $this->getProduct();
        if (!$product) {
            return false;
        }

        return !$product->isSalable();
    }

    public function shouldShowStockAlert(): bool
    {
        return $this->isEnabled() && $this->isProductOutOfStock();
    }

    public function getSubscribeUrl(): string
    {
        return $this->getUrl('lowstocknotification/alert/stock');
    }

    public function getUnsubscribeUrl(): string
    {
        return $this->getUrl('lowstocknotification/alert/unstock');
    }

    public function getStatusUrl(): string
    {
        return $this->getUrl('lowstocknotification/alert/status');
    }

    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login');
    }

    public function getCustomerEmail(): ?string
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }
        return null;
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getCustomerName(): ?string
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            return trim($customer->getFirstname() . ' ' . $customer->getLastname());
        }
        return null;
    }

    public function isGuestSubscriptionAllowed(): bool
    {
        return $this->helper->isGuestAllowed();
    }

    public function getProductId(): ?int
    {
        $product = $this->getProduct();
        return $product ? (int) $product->getId() : null;
    }

    public function getFormKey(): string
    {
        return (string) $this->formKeyService->getFormKey();
    }

    public function getHelper(): StockAlertHelper
    {
        return $this->helper;
    }

    public function getPlacement(): string
    {
        return $this->helper->getPlacement();
    }

    public function getPlacementClass(): string
    {
        $placement = $this->getPlacement();
        return 'stock-alert-placement-' . str_replace('_', '-', $placement);
    }
}
