<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Block\Adminhtml\Alert;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Panth\LowStockNotification\Block\Adminhtml\Alert\Edit;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Test\Unit\Block\Adminhtml\BackendBlockTestCase;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;

class EditTest extends BackendBlockTestCase
{
    use BuildsAlerts;

    private array $loads = [];
    private int $customerLookups = 0;

    private function block(array $params, array $row = [], array $repos = []): Edit
    {
        $this->loads = [];
        $this->customerLookups = 0;
        $factory = $this->createStub(StockAlertFactory::class);
        $factory->method('create')->willReturnCallback(fn() => $this->alert());

        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('load')->willReturnCallback(function ($model, $id) use ($row, &$resource) {
            $this->loads[] = $id;
            if ($row) {
                $model->setData($row + ['alert_id' => $id]);
            }
            return $resource;
        });

        $productRepository = $this->createStub(ProductRepositoryInterface::class);
        $productRepository->method('getById')->willReturnCallback(function ($id) use ($repos) {
            if (!empty($repos['productMissing'])) {
                throw new NoSuchEntityException(__('missing'));
            }
            $product = $this->createStub(Product::class);
            $product->method('getId')->willReturn((int)$id);
            return $product;
        });

        $customerRepository = $this->createStub(CustomerRepositoryInterface::class);
        $customerRepository->method('getById')->willReturnCallback(function ($id) use ($repos) {
            $this->customerLookups++;
            if (!empty($repos['customerMissing'])) {
                throw new NoSuchEntityException(__('missing'));
            }
            $customer = $this->createStub(CustomerInterface::class);
            $customer->method('getId')->willReturn((int)$id);
            return $customer;
        });

        $priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $priceCurrency->method('format')->willReturnCallback(
            static fn($amount, $includeContainer = true) => ($includeContainer ? '<span>' : '') . '$' . $amount
        );

        return new Edit(
            $this->context($params),
            $productRepository,
            $customerRepository,
            $priceCurrency,
            $factory,
            $resource
        );
    }

    public function testAlertIsLoadedOnceFromRequest(): void
    {
        $block = $this->block(['alert_id' => '15'], ['product_id' => 3, 'status' => 1]);

        $alert = $block->getAlert();
        $this->assertSame($alert, $block->getAlert());
        $this->assertSame(15, $alert->getId());
        $this->assertSame([15], $this->loads);
    }

    public function testNoIdGivesEmptyAlertWithoutLoading(): void
    {
        $block = $this->block([]);

        $this->assertNull($block->getAlert()->getId());
        $this->assertSame([], $this->loads);
        $this->assertFalse($block->canSendEmail());
    }

    public function testProductLookupAndMissingProduct(): void
    {
        $this->assertSame(3, $this->block(['alert_id' => 1], ['product_id' => 3])->getProduct()->getId());
        $this->assertNull(
            $this->block(['alert_id' => 1], ['product_id' => 3], ['productMissing' => true])->getProduct()
        );
    }

    public function testGuestAlertHasNoCustomerAndSkipsLookup(): void
    {
        $block = $this->block(['alert_id' => 1], ['customer_id' => null]);

        $this->assertNull($block->getCustomer());
        $this->assertSame(0, $this->customerLookups);
    }

    public function testCustomerLookupAndDeletedCustomer(): void
    {
        $this->assertSame(8, $this->block(['alert_id' => 1], ['customer_id' => 8])->getCustomer()->getId());
        $this->assertNull(
            $this->block(['alert_id' => 1], ['customer_id' => 8], ['customerMissing' => true])->getCustomer()
        );
    }

    public function testOnlyPendingAlertCanBeSent(): void
    {
        $this->assertTrue($this->block(['alert_id' => 1], ['status' => '1'])->canSendEmail());
        $this->assertFalse($this->block(['alert_id' => 1], ['status' => StockAlert::STATUS_SENT])->canSendEmail());
    }

    public function testStatusLabelsAndClasses(): void
    {
        $block = $this->block([]);

        $this->assertSame('Pending', (string)$block->getStatusLabel('1'));
        $this->assertSame('Sent', (string)$block->getStatusLabel(2));
        $this->assertSame('Cancelled', (string)$block->getStatusLabel(3));
        $this->assertSame('Unknown', (string)$block->getStatusLabel(null));
        $this->assertSame('lsn-status is-pending', $block->getStatusClass('1'));
        $this->assertSame('lsn-status is-sent', $block->getStatusClass(2));
        $this->assertSame('lsn-status is-cancelled', $block->getStatusClass(3));
        $this->assertSame('', $block->getStatusClass(7));
    }

    public function testActionUrlsCarryAlertId(): void
    {
        $block = $this->block(['alert_id' => 21], ['status' => 1]);

        $this->assertSame('*/*/delete?alert_id=21', $block->getDeleteUrl());
        $this->assertSame('*/*/send?alert_id=21', $block->getSendUrl());
        $this->assertSame('*/*/index', $block->getBackUrl());
    }

    public function testFormatPriceOmitsContainer(): void
    {
        $this->assertSame('$19.99', $this->block([])->formatPrice(19.99));
    }
}
