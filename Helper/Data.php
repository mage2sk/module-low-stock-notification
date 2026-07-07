<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'lowstocknotification/general/enabled';
    public const XML_PATH_ALLOW_GUESTS = 'lowstocknotification/general/allow_guests';

    public const XML_PATH_BOX_BACKGROUND_COLOR = 'lowstocknotification/design/box_background_color';
    public const XML_PATH_BOX_BORDER_COLOR = 'lowstocknotification/design/box_border_color';
    public const XML_PATH_TEXT_COLOR = 'lowstocknotification/design/text_color';
    public const XML_PATH_HEADING_COLOR = 'lowstocknotification/design/heading_color';
    public const XML_PATH_BUTTON_BG_FROM = 'lowstocknotification/design/button_bg_from';
    public const XML_PATH_BUTTON_BG_TO = 'lowstocknotification/design/button_bg_to';
    public const XML_PATH_BUTTON_TEXT_COLOR = 'lowstocknotification/design/button_text_color';
    public const XML_PATH_BUTTON_HOVER_EFFECT = 'lowstocknotification/design/button_hover_effect';

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

    public function getBoxBackgroundColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BOX_BACKGROUND_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#f0fdf4';
    }

    public function getBoxBorderColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BOX_BORDER_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#bbf7d0';
    }

    public function getTextColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TEXT_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#374151';
    }

    public function getHeadingColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_HEADING_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#111827';
    }

    public function getPrimaryColor(?int $storeId = null): string
    {
        return $this->getButtonBgFrom($storeId);
    }

    public function getButtonBgFrom(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_BG_FROM,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#10b981';
    }

    public function getButtonBgTo(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_BG_TO,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#059669';
    }

    public function getButtonTextColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_TEXT_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#ffffff';
    }

    public function isButtonHoverEffectEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_BUTTON_HOVER_EFFECT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getButtonGradientFrom(?int $storeId = null): string
    {
        return $this->getButtonBgFrom($storeId);
    }

    public function getButtonGradientTo(?int $storeId = null): string
    {
        return $this->getButtonBgTo($storeId);
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
