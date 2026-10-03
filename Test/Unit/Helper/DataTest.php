<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\LowStockNotification\Helper\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private array $calls = [];

    private function helper(array $flags = [], array $values = []): Data
    {
        $this->calls = [];
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            function ($path, $scope, $storeId = null) use ($flags) {
                $this->calls[] = [$path, $scope, $storeId];
                return (bool)($flags[$path] ?? false);
            }
        );
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path, $scope, $storeId = null) use ($values) {
                $this->calls[] = [$path, $scope, $storeId];
                return $values[$path] ?? null;
            }
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new Data($context);
    }

    public function testIsEnabledReadsStoreScopedFlag(): void
    {
        $helper = $this->helper([Data::XML_PATH_ENABLED => true]);

        $this->assertTrue($helper->isEnabled(3));
        $this->assertSame([[Data::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, 3]], $this->calls);
    }

    public function testIsEnabledFalseWhenFlagUnset(): void
    {
        $this->assertFalse($this->helper()->isEnabled());
    }

    public function testStockAlertEnabledMirrorsMasterSwitch(): void
    {
        $this->assertTrue($this->helper([Data::XML_PATH_ENABLED => true])->isStockAlertEnabled(2));
        $this->assertFalse($this->helper()->isStockAlertEnabled(2));
    }

    public function testGuestAndProductPageFlagsUseTheirOwnPaths(): void
    {
        $helper = $this->helper([Data::XML_PATH_ALLOW_GUESTS => true]);

        $this->assertTrue($helper->isGuestAllowed(1));
        $this->assertFalse($helper->isEnabledOnProductPage(1));
        $this->assertSame(Data::XML_PATH_ALLOW_GUESTS, $this->calls[0][0]);
        $this->assertSame(Data::XML_PATH_ENABLE_ON_PRODUCT_PAGE, $this->calls[1][0]);
    }

    public function testDisplayPositionDefaultsToAfterPrice(): void
    {
        $this->assertSame('after_price', $this->helper()->getDisplayPosition());
        $this->assertSame(
            'after_price',
            $this->helper([], [Data::XML_PATH_DISPLAY_POSITION => ''])->getPlacement()
        );
    }

    public function testPlacementReturnsConfiguredPosition(): void
    {
        $helper = $this->helper([], [Data::XML_PATH_DISPLAY_POSITION => 'below_description']);

        $this->assertSame('below_description', $helper->getPlacement(4));
        $this->assertSame([Data::XML_PATH_DISPLAY_POSITION, ScopeInterface::SCOPE_STORE, 4], $this->calls[0]);
    }
}
