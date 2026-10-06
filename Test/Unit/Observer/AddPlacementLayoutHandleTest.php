<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Layout\ProcessorInterface;
use Panth\LowStockNotification\Helper\Data;
use Panth\LowStockNotification\Observer\AddPlacementLayoutHandle;
use PHPUnit\Framework\TestCase;

class AddPlacementLayoutHandleTest extends TestCase
{
    private function helper(bool $enabled, bool $onProductPage, string $placement = 'after_price'): Data
    {
        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturn($enabled);
        $helper->method('isEnabledOnProductPage')->willReturn($onProductPage);
        $helper->method('getPlacement')->willReturn($placement);
        return $helper;
    }

    private function layoutNeverUpdated(): Layout
    {
        $layout = $this->createMock(Layout::class);
        $layout->expects($this->never())->method('getUpdate');
        return $layout;
    }

    public function testOtherPagesAreIgnored(): void
    {
        $observer = new AddPlacementLayoutHandle($this->helper(true, true));
        $observer->execute(new Observer([
            'layout' => $this->layoutNeverUpdated(),
            'full_action_name' => 'catalog_category_view',
        ]));
    }

    public function testDisabledModuleAddsNoHandle(): void
    {
        $observer = new AddPlacementLayoutHandle($this->helper(false, true));
        $observer->execute(new Observer([
            'layout' => $this->layoutNeverUpdated(),
            'full_action_name' => 'catalog_product_view',
        ]));
    }

    public function testDisabledOnProductPageAddsNoHandle(): void
    {
        $observer = new AddPlacementLayoutHandle($this->helper(true, false));
        $observer->execute(new Observer([
            'layout' => $this->layoutNeverUpdated(),
            'full_action_name' => 'catalog_product_view',
        ]));
    }

    public function testProductPageGetsPlacementHandle(): void
    {
        $update = $this->createMock(ProcessorInterface::class);
        $update->expects($this->once())
            ->method('addHandle')
            ->with('lowstocknotification_placement_below_add_to_cart');
        $layout = $this->createStub(Layout::class);
        $layout->method('getUpdate')->willReturn($update);

        $observer = new AddPlacementLayoutHandle($this->helper(true, true, 'below_add_to_cart'));
        $observer->execute(new Observer(['layout' => $layout, 'full_action_name' => 'catalog_product_view']));
    }
}
