<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Panth\LowStockNotification\Block\StockAlert;
use Panth\LowStockNotification\Helper\Data;
use Panth\LowStockNotification\Test\Unit\Fixture\CustomerSessionDouble;
use PHPUnit\Framework\TestCase;

class StockAlertTest extends TestCase
{
    private int $repositoryCalls = 0;

    private function product(bool $salable, int $id = 42): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('isSalable')->willReturn($salable);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    private function block(
        array $params = [],
        array $data = [],
        array $helperValues = [],
        ?CustomerSessionDouble $session = null,
        $repositoryResult = null
    ): StockAlert {
        $this->repositoryCalls = 0;
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(static fn($route) => 'https://shop.test/' . $route);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);

        $helperValues += ['enabled' => true, 'guests' => false, 'placement' => 'after_price'];
        $helper = $this->createStub(Data::class);
        $helper->method('isStockAlertEnabled')->willReturn($helperValues['enabled']);
        $helper->method('isGuestAllowed')->willReturn($helperValues['guests']);
        $helper->method('getPlacement')->willReturn($helperValues['placement']);
        $helper->method('isCompactStyle')->willReturn($helperValues['compact'] ?? true);

        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function () use ($repositoryResult) {
            $this->repositoryCalls++;
            if ($repositoryResult instanceof \Exception) {
                throw $repositoryResult;
            }
            return $repositoryResult;
        });

        $formKey = $this->createStub(FormKey::class);
        $formKey->method('getFormKey')->willReturn('fk123');

        return new StockAlert(
            $context,
            $helper,
            $session ?? new CustomerSessionDouble(),
            $repository,
            $formKey,
            $data
        );
    }

    public function testDisplayStyleComesFromHelper(): void
    {
        $this->assertTrue($this->block()->isCompact());
        $this->assertFalse($this->block([], [], ['compact' => false])->isCompact());
    }

    public function testProductFromBlockDataWinsOverRequest(): void
    {
        $product = $this->product(false, 9);
        $block = $this->block(['id' => 42], ['product' => $product]);

        $this->assertSame($product, $block->getProduct());
        $this->assertSame(9, $block->getProductId());
        $this->assertSame(0, $this->repositoryCalls);
    }

    public function testProductIsLoadedOnceFromRequestId(): void
    {
        $product = $this->product(false);
        $block = $this->block(['id' => '42'], [], [], null, $product);

        $this->assertSame($product, $block->getProduct());
        $this->assertSame($product, $block->getProduct());
        $this->assertSame(1, $this->repositoryCalls);
    }

    public function testMissingProductIsCachedAsNull(): void
    {
        $block = $this->block(['id' => 404], [], [], null, new NoSuchEntityException(__('nope')));

        $this->assertNull($block->getProduct());
        $this->assertNull($block->getProduct());
        $this->assertNull($block->getProductId());
        $this->assertSame(1, $this->repositoryCalls);
    }

    public function testNoRequestIdMeansNoProduct(): void
    {
        $block = $this->block();

        $this->assertNull($block->getProduct());
        $this->assertFalse($block->isProductOutOfStock());
        $this->assertSame(0, $this->repositoryCalls);
    }

    public function testAlertShownOnlyForOutOfStockProductWhenEnabled(): void
    {
        $this->assertTrue($this->block([], ['product' => $this->product(false)])->shouldShowStockAlert());
        $this->assertFalse($this->block([], ['product' => $this->product(true)])->shouldShowStockAlert());
        $this->assertFalse(
            $this->block([], ['product' => $this->product(false)], ['enabled' => false])->shouldShowStockAlert()
        );
    }

    public function testGuestCustomerHasNoIdentity(): void
    {
        $block = $this->block([], [], ['guests' => true]);

        $this->assertFalse($block->isCustomerLoggedIn());
        $this->assertNull($block->getCustomerEmail());
        $this->assertNull($block->getCustomerName());
        $this->assertTrue($block->isGuestSubscriptionAllowed());
    }

    public function testLoggedInCustomerIdentity(): void
    {
        $customer = new DataObject(['email' => 'mia@example.com', 'firstname' => 'Mia', 'lastname' => '']);
        $block = $this->block([], [], [], new CustomerSessionDouble(5, $customer));

        $this->assertTrue($block->isCustomerLoggedIn());
        $this->assertSame('mia@example.com', $block->getCustomerEmail());
        $this->assertSame('Mia', $block->getCustomerName(), 'Name is trimmed when last name is empty');
    }

    public function testPlacementClassUsesDashes(): void
    {
        $block = $this->block([], [], ['placement' => 'above_add_to_cart']);

        $this->assertSame('above_add_to_cart', $block->getPlacement());
        $this->assertSame('stock-alert-placement-above-add-to-cart', $block->getPlacementClass());
    }

    public function testFormKeyComesFromTheFormKeyService(): void
    {
        $this->assertSame('fk123', $this->block()->getFormKey());
    }

    public function testEndpointUrls(): void
    {
        $block = $this->block();

        $this->assertSame('https://shop.test/lowstocknotification/alert/stock', $block->getSubscribeUrl());
        $this->assertSame('https://shop.test/lowstocknotification/alert/unstock', $block->getUnsubscribeUrl());
        $this->assertSame('https://shop.test/lowstocknotification/alert/status', $block->getStatusUrl());
        $this->assertSame('https://shop.test/customer/account/login', $block->getLoginUrl());
        $this->assertInstanceOf(Data::class, $block->getHelper());
    }
}
