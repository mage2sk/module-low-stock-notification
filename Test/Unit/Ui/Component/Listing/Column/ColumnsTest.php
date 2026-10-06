<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Ui\Component\Listing\Column;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Ui\Component\Listing\Column\AlertActions;
use Panth\LowStockNotification\Ui\Component\Listing\Column\CustomerName;
use Panth\LowStockNotification\Ui\Component\Listing\Column\ProductName;
use PHPUnit\Framework\TestCase;

class ColumnsTest extends TestCase
{
    private function productColumn(): ProductName
    {
        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function ($id) {
            if ((int)$id === 404) {
                throw new NoSuchEntityException(__('missing'));
            }
            $product = $this->createStub(Product::class);
            $product->method('getName')->willReturn('Product ' . $id);
            return $product;
        });
        return new ProductName(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $repository
        );
    }

    private function customerColumn(): CustomerName
    {
        $repository = $this->createStub(CustomerRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function ($id) {
            if ((int)$id === 404) {
                throw new NoSuchEntityException(__('missing'));
            }
            $customer = $this->createStub(CustomerInterface::class);
            $customer->method('getFirstname')->willReturn('Ann');
            $customer->method('getLastname')->willReturn('Lee');
            return $customer;
        });
        return new CustomerName(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $repository
        );
    }

    private function actionsColumn(): AlertActions
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => $route . '/id/' . $params['alert_id']
        );
        return new AlertActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $url,
            [],
            ['name' => 'actions']
        );
    }

    public function testProductNameIsResolvedOrMarkedMissing(): void
    {
        $result = $this->productColumn()->prepareDataSource(['data' => ['items' => [
            ['product_id' => 7],
            ['product_id' => 404],
            ['alert_id' => 1],
        ]]]);
        $items = $result['data']['items'];

        $this->assertSame('Product 7', $items[0]['product_name']);
        $this->assertSame('Product not found', (string)$items[1]['product_name']);
        $this->assertArrayNotHasKey('product_name', $items[2]);
    }

    public function testDataSourceWithoutItemsIsReturnedUnchanged(): void
    {
        $source = ['data' => ['totalRecords' => 0]];

        $this->assertSame($source, $this->productColumn()->prepareDataSource($source));
        $this->assertSame($source, $this->customerColumn()->prepareDataSource($source));
        $this->assertSame($source, $this->actionsColumn()->prepareDataSource($source));
    }

    public function testCustomerNameResolution(): void
    {
        $result = $this->customerColumn()->prepareDataSource(['data' => ['items' => [
            ['customer_name' => 'Stored Name', 'customer_id' => 9],
            ['customer_name' => '', 'customer_id' => 9],
            ['customer_id' => 404],
            ['customer_id' => 0],
            [],
        ]]]);
        $names = array_map(static fn($item) => (string)$item['customer_name'], $result['data']['items']);

        $this->assertSame(['Stored Name', 'Ann Lee', 'N/A', 'Guest', 'Guest'], $names);
    }

    public function testPendingAlertGetsViewDeleteAndSendActions(): void
    {
        $result = $this->actionsColumn()->prepareDataSource(['data' => ['items' => [
            ['alert_id' => 3, 'status' => StockAlert::STATUS_ACTIVE],
        ]]]);
        $actions = $result['data']['items'][0]['actions'];

        $this->assertSame(['view', 'delete', 'send'], array_keys($actions));
        $this->assertSame('lowstocknotification/alert/view/id/3', $actions['view']['href']);
        $this->assertSame('lowstocknotification/alert/delete/id/3', $actions['delete']['href']);
        $this->assertTrue($actions['delete']['post']);
        $this->assertSame('Delete Alert', (string)$actions['delete']['confirm']['title']);
        $this->assertSame('lowstocknotification/alert/send/id/3', $actions['send']['href']);
        $this->assertTrue($actions['send']['post']);
    }

    public function testNonPendingAlertsCannotBeSent(): void
    {
        $result = $this->actionsColumn()->prepareDataSource(['data' => ['items' => [
            ['alert_id' => 4, 'status' => StockAlert::STATUS_SENT],
            ['alert_id' => 5, 'status' => (string)StockAlert::STATUS_CANCELLED],
        ]]]);

        $this->assertSame(['view', 'delete'], array_keys($result['data']['items'][0]['actions']));
        $this->assertSame(['view', 'delete'], array_keys($result['data']['items'][1]['actions']));
    }

    public function testStringStatusFromDatabaseStillAllowsSend(): void
    {
        $result = $this->actionsColumn()->prepareDataSource(['data' => ['items' => [
            ['alert_id' => 6, 'status' => '1'],
        ]]]);

        $this->assertArrayHasKey('send', $result['data']['items'][0]['actions']);
    }

    public function testRowsWithoutIdGetNoActions(): void
    {
        $result = $this->actionsColumn()->prepareDataSource(['data' => ['items' => [['status' => 1]]]]);

        $this->assertSame([['status' => 1]], $result['data']['items']);
    }
}
