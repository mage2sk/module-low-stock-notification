<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Model\Source;

use Panth\LowStockNotification\Model\Config\Source\Placement;
use Panth\LowStockNotification\Model\Source\Status;
use Panth\LowStockNotification\Model\StockAlert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SourceModelsTest extends TestCase
{
    public function testStatusOptionsCoverEveryAlertState(): void
    {
        $map = [];
        foreach ((new Status())->toOptionArray() as $option) {
            $map[$option['value']] = (string)$option['label'];
        }

        $this->assertSame([
            StockAlert::STATUS_ACTIVE => 'Pending',
            StockAlert::STATUS_SENT => 'Sent',
            StockAlert::STATUS_CANCELLED => 'Cancelled',
        ], $map);
    }

    public function testPlacementOptionArrayMatchesToArray(): void
    {
        $source = new Placement();
        $fromOptions = [];
        foreach ($source->toOptionArray() as $option) {
            $fromOptions[$option['value']] = (string)$option['label'];
        }
        $fromArray = array_map('strval', $source->toArray());

        $this->assertSame($fromArray, $fromOptions);
        $this->assertCount(5, $fromOptions);
        $this->assertSame('After Price (Default)', $fromOptions[Placement::AFTER_PRICE]);
    }

    public static function containerProvider(): array
    {
        return [
            'after price' => [Placement::AFTER_PRICE, 'after', 'product.info.price'],
            'above cart' => [Placement::ABOVE_ADD_TO_CART, 'before', 'product.info.addtocart'],
            'below cart' => [Placement::BELOW_ADD_TO_CART, 'after', 'product.info.addtocart.additional'],
            'above description' => [Placement::ABOVE_DESCRIPTION, 'before', 'product.info.overview'],
            'below description' => [Placement::BELOW_DESCRIPTION, 'after', 'product.info.details'],
            'unknown falls back' => ['sidebar', 'after', 'product.info.price'],
            'null falls back' => [null, 'after', 'product.info.price'],
        ];
    }

    #[DataProvider('containerProvider')]
    public function testContainerConfigForPlacement($placement, string $position, string $sibling): void
    {
        $this->assertSame(
            ['container' => 'product.info.main', 'position' => $position, 'sibling' => $sibling],
            (new Placement())->getContainerConfig($placement)
        );
    }
}
