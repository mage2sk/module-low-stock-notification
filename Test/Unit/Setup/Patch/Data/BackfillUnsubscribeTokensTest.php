<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Panth\LowStockNotification\Setup\Patch\Data\BackfillUnsubscribeTokens;
use PHPUnit\Framework\TestCase;

class BackfillUnsubscribeTokensTest extends TestCase
{
    private function dataSetup(AdapterInterface $connection): ModuleDataSetupInterface
    {
        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturnCallback(static fn($t) => 'pfx_' . $t);
        return $setup;
    }

    public function testMissingTableIsSkipped(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->once())->method('isTableExists')
            ->with('pfx_panth_stock_alert')->willReturn(false);
        $connection->expects($this->never())->method('select');
        $connection->expects($this->never())->method('update');

        $patch = new BackfillUnsubscribeTokens($this->dataSetup($connection));
        $this->assertSame($patch, $patch->apply());
    }

    public function testMissingColumnIsSkipped(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(true);
        $connection->expects($this->once())->method('tableColumnExists')
            ->with('pfx_panth_stock_alert', 'unsubscribe_token')->willReturn(false);
        $connection->expects($this->never())->method('update');

        (new BackfillUnsubscribeTokens($this->dataSetup($connection)))->apply();
    }

    public function testEveryAlertWithoutTokenGetsAUniqueToken(): void
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $updates = [];
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(true);
        $connection->method('tableColumnExists')->willReturn(true);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturn(['3', '8']);
        $connection->method('update')->willReturnCallback(
            function ($table, $bind, $where = '') use (&$updates) {
                $updates[] = [$table, $bind, $where];
                return 1;
            }
        );

        (new BackfillUnsubscribeTokens($this->dataSetup($connection)))->apply();

        $this->assertCount(2, $updates);
        $this->assertSame('pfx_panth_stock_alert', $updates[0][0]);
        $this->assertSame(['alert_id = ?' => 3], $updates[0][2]);
        $this->assertSame(['alert_id = ?' => 8], $updates[1][2]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $updates[0][1]['unsubscribe_token']);
        $this->assertNotSame($updates[0][1]['unsubscribe_token'], $updates[1][1]['unsubscribe_token']);
    }

    public function testPatchHasNoDependenciesOrAliases(): void
    {
        $patch = new BackfillUnsubscribeTokens($this->createStub(ModuleDataSetupInterface::class));

        $this->assertSame([], BackfillUnsubscribeTokens::getDependencies());
        $this->assertSame([], $patch->getAliases());
    }
}
