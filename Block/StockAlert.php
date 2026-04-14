<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Stock Alert Block
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\LowStockNotification\Helper\Data as StockAlertHelper;

class StockAlert extends Template
{
    /**
     * @var StockAlertHelper
     */
    private StockAlertHelper $helper;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface|null|false
     */
    private $currentProduct = null;

    /**
     * @param Context $context
     * @param StockAlertHelper $helper
     * @param CustomerSession $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        StockAlertHelper $helper,
        CustomerSession $customerSession,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * Check if Stock Alert is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->helper->isStockAlertEnabled();
    }

    /**
     * Get current product
     *
     * Uses the product set via layout data or fetches from request param.
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        if ($this->currentProduct === null) {
            // Try to get from block data first
            if ($this->hasData('product')) {
                $this->currentProduct = $this->getData('product');
            } else {
                // Fallback: get from request parameter
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

    /**
     * Check if product is out of stock
     *
     * @return bool
     */
    public function isProductOutOfStock(): bool
    {
        $product = $this->getProduct();
        if (!$product) {
            return false;
        }

        return !$product->isSalable();
    }

    /**
     * Should show stock alert?
     *
     * @return bool
     */
    public function shouldShowStockAlert(): bool
    {
        return $this->isEnabled() && $this->isProductOutOfStock();
    }

    /**
     * Get stock alert subscribe URL
     *
     * @return string
     */
    public function getSubscribeUrl(): string
    {
        return $this->getUrl('lowstocknotification/alert/stock');
    }

    /**
     * Get stock alert unsubscribe URL
     *
     * @return string
     */
    public function getUnsubscribeUrl(): string
    {
        return $this->getUrl('lowstocknotification/alert/unstock');
    }

    /**
     * Get customer email if logged in
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }
        return null;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get customer name
     *
     * @return string|null
     */
    public function getCustomerName(): ?string
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            return trim($customer->getFirstname() . ' ' . $customer->getLastname());
        }
        return null;
    }

    /**
     * Check if guests are allowed to subscribe
     *
     * @return bool
     */
    public function isGuestSubscriptionAllowed(): bool
    {
        return $this->helper->isGuestAllowed();
    }

    /**
     * Get product ID
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        $product = $this->getProduct();
        return $product ? (int) $product->getId() : null;
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get helper
     *
     * @return StockAlertHelper
     */
    public function getHelper(): StockAlertHelper
    {
        return $this->helper;
    }

    /**
     * Get placement configuration
     *
     * @return string
     */
    public function getPlacement(): string
    {
        return $this->helper->getPlacement();
    }

    /**
     * Get placement CSS class
     *
     * @return string
     */
    public function getPlacementClass(): string
    {
        $placement = $this->getPlacement();
        return 'stock-alert-placement-' . str_replace('_', '-', $placement);
    }
}
