<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Panth\LowStockNotification\Model\Config\Source\DisplayStyle;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'lowstocknotification/general/enabled';
    public const XML_PATH_ALLOW_GUESTS = 'lowstocknotification/general/allow_guests';
    public const XML_PATH_DISPLAY_STYLE = 'lowstocknotification/general/display_style';

    public const XML_PATH_ENABLE_ON_PRODUCT_PAGE = 'lowstocknotification/placement/enable_on_product_page';
    public const XML_PATH_DISPLAY_POSITION = 'lowstocknotification/placement/display_position';

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isGuestAllowed(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_GUESTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDisplayStyle(?int $storeId = null): string
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_STYLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value === DisplayStyle::INLINE ? DisplayStyle::INLINE : DisplayStyle::COMPACT;
    }

    public function isCompactStyle(?int $storeId = null): bool
    {
        return $this->getDisplayStyle($storeId) === DisplayStyle::COMPACT;
    }

    public function isStockAlertEnabled(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId);
    }

    public function isEnabledOnProductPage(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_ON_PRODUCT_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDisplayPosition(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'after_price';
    }

    public function getPlacement(?int $storeId = null): string
    {
        return $this->getDisplayPosition($storeId);
    }
}
