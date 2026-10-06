<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Model;

use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use PHPUnit\Framework\TestCase;

class StockAlertTest extends TestCase
{
    use BuildsAlerts;

    public function testIdentitiesUseCacheTagAndId(): void
    {
        $this->assertSame(['panth_stock_alert_17'], $this->alert(['alert_id' => 17])->getIdentities());
    }

    public function testStatusConstantsAreDistinct(): void
    {
        $this->assertSame(
            [1, 2, 3],
            [StockAlert::STATUS_ACTIVE, StockAlert::STATUS_SENT, StockAlert::STATUS_CANCELLED]
        );
    }

    public function testIdIsReadFromAlertIdField(): void
    {
        $alert = $this->alert(['alert_id' => 9, 'email' => 'a@example.com']);

        $this->assertSame(9, $alert->getId());
        $this->assertSame(9, $alert->getAlertId());
    }
}
