<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Block\Adminhtml;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Panth\LowStockNotification\Block\Adminhtml\Dashboard;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;

class DashboardTest extends BackendBlockTestCase
{
    private array $collections = [];
    private int $repositoryCalls = 0;
    private array $selectCalls = [];
    private string $now = '2026-10-03 21:15:00';

    private function dashboard(int $size = 0, ?AdapterInterface $connection = null): Dashboard
    {
        $this->collections = [];
        $this->repositoryCalls = 0;
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($size, $connection) {
            $collection = new FakeAlertCollection([], $size, null, $connection);
            $this->collections[] = $collection;
            return $collection;
        });
        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function ($id) {
            $this->repositoryCalls++;
            if ($id === 404) {
                throw new NoSuchEntityException(__('missing'));
            }
            $product = $this->createStub(Product::class);
            $product->method('getName')->willReturn('Name ' . $id);
            return $product;
        });
        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('date')->willReturnCallback(
            fn() => new \DateTime($this->now, new \DateTimeZone('America/Chicago'))
        );
        return new Dashboard($this->context(), $factory, $repository, [], $timezone);
    }

    private function connection(array $rows, array $createdAt = []): AdapterInterface
    {
        $this->selectCalls = [];
        $select = $this->createStub(Select::class);
        foreach (['from', 'where', 'group', 'order', 'limit', 'having'] as $method) {
            $select->method($method)->willReturnCallback(function (...$args) use ($method, &$select) {
                $this->selectCalls[] = [$method, $args];
                return $select;
            });
        }
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn($rows);
        $connection->method('fetchCol')->willReturn($createdAt);
        return $connection;
    }

    private function selectArgs(string $method): array
    {
        $found = [];
        foreach ($this->selectCalls as [$name, $args]) {
            if ($name === $method) {
                $found[] = array_values(array_filter($args, static fn($a) => $a !== null));
            }
        }
        return $found;
    }

    public function testProductNameIsCachedPerProduct(): void
    {
        $dashboard = $this->dashboard();

        $this->assertSame('Name 7', $dashboard->getProductName(7));
        $this->assertSame('Name 7', $dashboard->getProductName(7));
        $this->assertSame('Name 8', $dashboard->getProductName(8));
        $this->assertSame(2, $this->repositoryCalls);
    }

    public function testMissingProductFallsBackToIdLabel(): void
    {
        $dashboard = $this->dashboard();

        $this->assertSame('Product #404', $dashboard->getProductName(404));
        $this->assertSame('Product #404', $dashboard->getProductName(404));
        $this->assertSame(1, $this->repositoryCalls);
    }

    public function testStatusCountsFilterByStatus(): void
    {
        $dashboard = $this->dashboard(6);

        $this->assertSame(6, $dashboard->getTotalAlertsCount());
        $this->assertSame([], $this->collections[0]->filters);
        $this->assertSame(6, $dashboard->getPendingAlertsCount());
        $this->assertSame([['status', StockAlert::STATUS_ACTIVE]], $this->collections[1]->filters);
        $dashboard->getSentAlertsCount();
        $this->assertSame([['status', StockAlert::STATUS_SENT]], $this->collections[2]->filters);
        $dashboard->getCancelledAlertsCount();
        $this->assertSame([['status', StockAlert::STATUS_CANCELLED]], $this->collections[3]->filters);
    }

    public function testTodayCountsStartAtStoreMidnightConvertedToUtc(): void
    {
        $dashboard = $this->dashboard(2);
        $midnight = '2026-10-03 05:00:00';

        $this->assertSame(2, $dashboard->getTodayAlertsCount());
        $this->assertSame([['created_at', ['gteq' => $midnight]]], $this->collections[0]->filters);
        $dashboard->getTodaySentCount();
        $this->assertSame(
            [['status', StockAlert::STATUS_SENT], ['sent_at', ['gteq' => $midnight]]],
            $this->collections[1]->filters
        );
    }

    public function testRecentAlertsAreNewestFirstAndLimited(): void
    {
        $recent = $this->dashboard()->getRecentAlerts(5);

        $this->assertSame([['created_at', 'DESC']], $recent->orders);
        $this->assertSame(5, $recent->pageSize);
    }

    public function testStatusLabelsAndClasses(): void
    {
        $dashboard = $this->dashboard();

        $this->assertSame('Pending', (string)$dashboard->getStatusLabel(1));
        $this->assertSame('Sent', (string)$dashboard->getStatusLabel('2'));
        $this->assertSame('Cancelled', (string)$dashboard->getStatusLabel(3));
        $this->assertSame('Unknown', (string)$dashboard->getStatusLabel(99));
        $this->assertSame('lsn-status is-pending', $dashboard->getStatusClass(1));
        $this->assertSame('lsn-status is-sent', $dashboard->getStatusClass(2));
        $this->assertSame('lsn-status is-cancelled', $dashboard->getStatusClass(3));
        $this->assertSame('', $dashboard->getStatusClass(0));
    }

    public function testUrls(): void
    {
        $dashboard = $this->dashboard();

        $this->assertSame('lowstocknotification/alert/view?alert_id=12', $dashboard->getViewAlertUrl(12));
        $this->assertSame('lowstocknotification/alert/index', $dashboard->getManageAlertsUrl());
    }

    public function testTrendDataGroupsUtcTimestampsByStoreDayWithZeroFill(): void
    {
        $dashboard = $this->dashboard(0, $this->connection([], [
            '2026-09-28 12:00:00',
            '2026-10-01 02:39:31',
            '2026-10-01 04:59:59',
            '2026-10-04 01:00:00',
        ]));

        $this->assertSame([
            ['date' => '2026-09-27', 'count' => 0],
            ['date' => '2026-09-28', 'count' => 1],
            ['date' => '2026-09-29', 'count' => 0],
            ['date' => '2026-09-30', 'count' => 2],
            ['date' => '2026-10-01', 'count' => 0],
            ['date' => '2026-10-02', 'count' => 0],
            ['date' => '2026-10-03', 'count' => 1],
        ], $dashboard->getAlertTrendData());
        $this->assertSame([['created_at >= ?', '2026-09-27 05:00:00']], $this->selectArgs('where'));
    }

    public function testTrendDataCrossesMonthBoundaryFromStoreDate(): void
    {
        $this->now = '2026-03-02 10:00:00';
        $dashboard = $this->dashboard(0, $this->connection([]));
        $dates = array_column($dashboard->getAlertTrendData(), 'date');

        $this->assertSame('2026-02-24', $dates[0]);
        $this->assertSame('2026-03-02', $dates[6]);
    }

    public function testMostRequestedProductsQueryActiveAlertsWithLimit(): void
    {
        $rows = [['product_id' => 3, 'alert_count' => 9]];
        $dashboard = $this->dashboard(0, $this->connection($rows));

        $this->assertSame($rows, $dashboard->getMostRequestedProducts(4));
        $this->assertSame([['status = ?', StockAlert::STATUS_ACTIVE]], $this->selectArgs('where'));
        $this->assertSame([[4]], $this->selectArgs('limit'));
        $this->assertSame([['alert_count DESC']], $this->selectArgs('order'));
        $this->assertSame(['main_table' => 'panth_stock_alert'], $this->selectArgs('from')[0][0]);
    }

    public function testCriticalAlertsRequireAtLeastFiveRequests(): void
    {
        $rows = [['product_id' => 8, 'alert_count' => 5]];
        $dashboard = $this->dashboard(0, $this->connection($rows));

        $this->assertSame($rows, $dashboard->getCriticalStockAlerts());
        $this->assertSame([['alert_count >= ?', 5]], $this->selectArgs('having'));
        $this->assertSame([['product_id']], $this->selectArgs('group'));
    }
}
