<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * XML path constants for general settings
     */
    public const XML_PATH_ENABLED = 'lowstocknotification/general/enabled';
    public const XML_PATH_ALLOW_GUESTS = 'lowstocknotification/general/allow_guests';

    /**
     * XML path constants for design settings
     */
    public const XML_PATH_BOX_BACKGROUND_COLOR = 'lowstocknotification/design/box_background_color';
    public const XML_PATH_BOX_BORDER_COLOR = 'lowstocknotification/design/box_border_color';
    public const XML_PATH_TEXT_COLOR = 'lowstocknotification/design/text_color';
    public const XML_PATH_HEADING_COLOR = 'lowstocknotification/design/heading_color';
    public const XML_PATH_BUTTON_BG_FROM = 'lowstocknotification/design/button_bg_from';
    public const XML_PATH_BUTTON_BG_TO = 'lowstocknotification/design/button_bg_to';
    public const XML_PATH_BUTTON_TEXT_COLOR = 'lowstocknotification/design/button_text_color';
    public const XML_PATH_BUTTON_HOVER_EFFECT = 'lowstocknotification/design/button_hover_effect';

    /**
     * XML path constants for placement settings
     */
    public const XML_PATH_ENABLE_ON_PRODUCT_PAGE = 'lowstocknotification/placement/enable_on_product_page';
    public const XML_PATH_DISPLAY_POSITION = 'lowstocknotification/placement/display_position';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if guest subscriptions are allowed
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isGuestAllowed(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_GUESTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get box background color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getBoxBackgroundColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BOX_BACKGROUND_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#f0fdf4';
    }

    /**
     * Get box border color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getBoxBorderColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BOX_BORDER_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#bbf7d0';
    }

    /**
     * Get text color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTextColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TEXT_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#374151';
    }

    /**
     * Get heading color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getHeadingColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_HEADING_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#111827';
    }

    /**
     * Get primary color (used for accents and highlights)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPrimaryColor(?int $storeId = null): string
    {
        return $this->getButtonBgFrom($storeId);
    }

    /**
     * Get button background gradient start color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getButtonBgFrom(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_BG_FROM,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#10b981';
    }

    /**
     * Get button background gradient end color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getButtonBgTo(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_BG_TO,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#059669';
    }

    /**
     * Get button text color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getButtonTextColor(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON_TEXT_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '#ffffff';
    }

    /**
     * Check if button hover effect is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isButtonHoverEffectEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_BUTTON_HOVER_EFFECT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get button gradient from color (backward compatibility alias)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getButtonGradientFrom(?int $storeId = null): string
    {
        return $this->getButtonBgFrom($storeId);
    }

    /**
     * Get button gradient to color (backward compatibility alias)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getButtonGradientTo(?int $storeId = null): string
    {
        return $this->getButtonBgTo($storeId);
    }

    /**
     * Check if stock alert is enabled (wrapper method)
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStockAlertEnabled(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId);
    }

    /**
     * Check if stock alert is enabled on product page
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledOnProductPage(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_ON_PRODUCT_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get display position
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDisplayPosition(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'after_price';
    }

    /**
     * Get placement position for stock alert box
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPlacement(?int $storeId = null): string
    {
        return $this->getDisplayPosition($storeId);
    }
}
