<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Panth\LowStockNotification\Cron\StockAlertNotification;
use Panth\LowStockNotification\Helper\Data;
use Panth\LowStockNotification\Model\EmailSender;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StockAlertNotificationTest extends TestCase
{
    use BuildsAlerts;

    private array $collections = [];
    private array $sent = [];
    private array $errors = [];

    private function cron(array $batches, array $enabledStores, array $salable, array $failingProducts = []): StockAlertNotification
    {
        $this->collections = [];
        $this->sent = [];
        $this->errors = [];

        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnCallback(function () use (&$batches) {
            $collection = new FakeAlertCollection(array_shift($batches) ?? []);
            $this->collections[] = $collection;
            return $collection;
        });

        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(
            function ($id, $edit, $storeId) use ($salable, $failingProducts) {
                if (in_array($id, $failingProducts, true)) {
                    throw new \RuntimeException('boom ' . $id);
                }
                $product = $this->createStub(Product::class);
                $product->method('isSalable')->willReturn(in_array($id, $salable, true));
                $product->method('getId')->willReturn($id);
                $product->method('getStoreId')->willReturn($storeId);
                return $product;
            }
        );

        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturnCallback(
            static fn($storeId = null) => in_array($storeId, $enabledStores, true)
        );

        $sender = $this->createStub(EmailSender::class);
        $sender->method('sendAlertEmail')->willReturnCallback(function ($alert, $product = null) {
            $this->sent[] = [(int)$alert->getId(), $product ? $product->getId() : null, $product ? $product->getStoreId() : null];
            return true;
        });

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function ($message) {
            $this->errors[] = $message;
        });

        return new StockAlertNotification($factory, $repository, $sender, $helper, $logger);
    }

    private function alerts(int $from, int $to, int $productId = 100, int $storeId = 1): array
    {
        $alerts = [];
        for ($id = $from; $id <= $to; $id++) {
            $alerts[] = $this->alert([
                'alert_id' => $id,
                'product_id' => $productId,
                'store_id' => $storeId,
                'status' => StockAlert::STATUS_ACTIVE,
            ]);
        }
        return $alerts;
    }

    public function testOnlySalableProductsInEnabledStoresAreNotified(): void
    {
        $batch = array_merge(
            $this->alerts(1, 1, 100, 1),
            $this->alerts(2, 2, 200, 1),
            $this->alerts(3, 3, 100, 2)
        );
        $this->cron([$batch], [1], [100])->execute();

        $this->assertSame([[1, 100, 1]], $this->sent);
        $this->assertSame([], $this->errors);
    }

    public function testQueryTargetsActiveAlertsInIdOrder(): void
    {
        $this->cron([[]], [1], [])->execute();

        $collection = $this->collections[0];
        $this->assertSame(StockAlert::STATUS_ACTIVE, $collection->filterValue('status'));
        $this->assertSame(['gt' => 0], $collection->filterValue('alert_id'));
        $this->assertSame([['alert_id', 'ASC']], $collection->orders);
        $this->assertSame(200, $collection->pageSize);
        $this->assertSame(1, $collection->curPage);
    }

    public function testFullBatchTriggersNextPageFromLastId(): void
    {
        $this->cron([$this->alerts(1, 200), $this->alerts(201, 203)], [1], [100])->execute();

        $this->assertCount(2, $this->collections);
        $this->assertSame(['gt' => 200], $this->collections[1]->filterValue('alert_id'));
        $this->assertCount(203, $this->sent);
    }

    public function testShortBatchStopsPaging(): void
    {
        $this->cron([$this->alerts(1, 199)], [1], [100])->execute();

        $this->assertCount(1, $this->collections);
    }

    public function testFailureOnOneAlertIsLoggedAndOthersContinue(): void
    {
        $batch = array_merge($this->alerts(1, 1, 300), $this->alerts(2, 2, 100));
        $this->cron([$batch], [1], [100], [300])->execute();

        $this->assertSame([[2, 100, 1]], $this->sent);
        $this->assertSame(['Stock alert error (alert 1): boom 300'], $this->errors);
    }
}
