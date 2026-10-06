<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Alert;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Controller\Alert\Stock;
use Panth\LowStockNotification\Helper\Data;
use Panth\LowStockNotification\Model\RateLimiter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use Panth\LowStockNotification\Test\Unit\Fixture\CustomerSessionDouble;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;
use PHPUnit\Framework\TestCase;

class StockTest extends TestCase
{
    use BuildsAlerts;

    private array $json = [];
    private ?int $httpCode = null;
    private array $saved = [];
    private ?FakeAlertCollection $existing = null;
    private CustomerSessionDouble $session;

    private const PRODUCT_ID = 42;

    /**
     * @param array $options enabled, allowed, guests, existing, product, saveFailure, session
     */
    private function controller(array $params, array $options = []): Stock
    {
        $this->json = [];
        $this->httpCode = null;
        $this->saved = [];
        $options += [
            'enabled' => true,
            'allowed' => true,
            'guests' => true,
            'existing' => 0,
            'product' => 'oos',
            'saveFailure' => null,
            'session' => new CustomerSessionDouble(),
        ];
        $this->session = $options['session'];

        $result = $this->createStub(Json::class);
        $result->method('setData')->willReturnCallback(function ($data) use (&$result) {
            $this->json = $data;
            return $result;
        });
        $result->method('setHttpResponseCode')->willReturnCallback(function ($code) use (&$result) {
            $this->httpCode = $code;
            return $result;
        });
        $jsonFactory = $this->createStub(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($result);

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->existing = new FakeAlertCollection([], $options['existing']);
        $factory = $this->createStub(StockAlertFactory::class);
        $factory->method('create')->willReturnCallback(fn() => $this->alertWithCollection($this->existing));

        $helper = $this->createStub(Data::class);
        $helper->method('isStockAlertEnabled')->willReturn($options['enabled']);
        $helper->method('isGuestAllowed')->willReturn($options['guests']);

        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function () use ($options) {
            if ($options['product'] === 'missing') {
                throw new NoSuchEntityException(__('missing'));
            }
            $product = $this->createStub(Product::class);
            $product->method('getStatus')->willReturn($options['product'] === 'disabled' ? 2 : 1);
            $product->method('getWebsiteIds')->willReturn($options['product'] === 'other_site' ? ['2'] : ['1', '3']);
            $product->method('isSalable')->willReturn($options['product'] === 'in_stock');
            return $product;
        });

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);

        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('save')->willReturnCallback(function ($alert) use ($options, &$resource) {
            if ($options['saveFailure']) {
                throw $options['saveFailure'];
            }
            $alert->setData('alert_id', 900 + count($this->saved));
            $this->saved[] = $alert->getData();
            return $resource;
        });

        $limiter = $this->createStub(RateLimiter::class);
        $limiter->method('isAllowed')->willReturn($options['allowed']);

        return new Stock(
            $jsonFactory,
            $this->session,
            $storeManager,
            $factory,
            $helper,
            $repository,
            $request,
            $resource,
            $limiter
        );
    }

    private function guestParams(array $override = []): array
    {
        return $override + [
            'product_id' => (string)self::PRODUCT_ID,
            'email' => ' guest@example.com ',
            'customer_name' => ' Jane Guest ',
        ];
    }

    private function assertError(string $message): void
    {
        $this->assertTrue($this->json['error'] ?? false);
        $this->assertSame($message, (string)$this->json['message']);
        $this->assertSame([], $this->saved);
    }

    private function loggedInSession(array $values = []): CustomerSessionDouble
    {
        $customer = new DataObject(['email' => 'member@example.com', 'firstname' => 'Mia', 'lastname' => 'Park']);
        return new CustomerSessionDouble(7, $customer, $values);
    }

    public function testDisabledModuleRejects(): void
    {
        $this->controller($this->guestParams(), ['enabled' => false])->execute();
        $this->assertError('Stock alerts are disabled.');
    }

    public function testRateLimitedRequestGets429(): void
    {
        $this->controller($this->guestParams(), ['allowed' => false])->execute();

        $this->assertSame(429, $this->httpCode);
        $this->assertError('Too many requests. Please try again later.');
    }

    public function testMissingProductIdIsRejected(): void
    {
        $this->controller($this->guestParams(['product_id' => 'abc']))->execute();
        $this->assertError('Product ID is required.');
    }

    public function testGuestsMustLogInWhenGuestSubscriptionsAreOff(): void
    {
        $this->controller($this->guestParams(), ['guests' => false])->execute();
        $this->assertError('Please log in to subscribe to stock alerts.');
    }

    public function testGuestNeedsEmail(): void
    {
        $this->controller($this->guestParams(['email' => '   ']))->execute();
        $this->assertError('Email address is required.');
    }

    public function testNonStringEmailIsTreatedAsMissing(): void
    {
        $this->controller($this->guestParams(['email' => ['a@example.com']]))->execute();
        $this->assertError('Email address is required.');
    }

    public function testGuestNeedsName(): void
    {
        $this->controller($this->guestParams(['customer_name' => '']))->execute();
        $this->assertError('Name is required for guests.');
    }

    public function testOverlongNameIsRejected(): void
    {
        $this->controller($this->guestParams(['customer_name' => str_repeat('a', 256)]))->execute();
        $this->assertError('Name is too long. Maximum 255 characters allowed.');
    }

    public function testNameMadeOnlyOfMarkupIsRejected(): void
    {
        $this->controller($this->guestParams(['customer_name' => '<b></b>']))->execute();
        $this->assertError('Please enter a valid name.');
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->controller($this->guestParams(['email' => 'not-an-email']))->execute();
        $this->assertError('Please enter a valid email address.');
    }

    public function testOverlongEmailIsRejected(): void
    {
        $email = str_repeat('a', 250) . '@example.com';
        $this->controller($this->guestParams(['email' => $email]))->execute();
        $this->assertError('Please enter a valid email address.');
    }

    public function testUnknownDisabledOrForeignProductsAreNotFound(): void
    {
        foreach (['missing', 'disabled', 'other_site'] as $state) {
            $this->controller($this->guestParams(), ['product' => $state])->execute();
            $this->assertError('Product not found.');
        }
    }

    public function testInStockProductCannotBeSubscribed(): void
    {
        $this->controller($this->guestParams(), ['product' => 'in_stock'])->execute();
        $this->assertError('This product is currently in stock.');
    }

    public function testGuestDuplicateGetsNeutralSuccessWithoutNewRow(): void
    {
        $this->controller($this->guestParams(), ['existing' => 1])->execute();

        $this->assertTrue($this->json['success']);
        $this->assertSame('You will be notified when this product is back in stock.', (string)$this->json['message']);
        $this->assertSame([], $this->saved);
        $this->assertSame(self::PRODUCT_ID, $this->existing->filterValue('product_id'));
        $this->assertSame('guest@example.com', $this->existing->filterValue('email'));
        $this->assertSame(StockAlert::STATUS_ACTIVE, $this->existing->filterValue('status'));
    }

    public function testLoggedInDuplicateIsReported(): void
    {
        $this->controller(['product_id' => self::PRODUCT_ID], ['existing' => 2, 'session' => $this->loggedInSession()])
            ->execute();

        $this->assertError('You are already subscribed to stock alerts for this product.');
    }

    public function testGuestSubscriptionIsSavedAndRememberedInSession(): void
    {
        $session = new CustomerSessionDouble(null, null, [Stock::SESSION_ALERT_IDS => ['5', 900]]);
        $this->controller(
            $this->guestParams(['customer_name' => ' <i>Jane</i> Guest ']),
            ['session' => $session]
        )->execute();

        $this->assertTrue($this->json['success']);
        $this->assertCount(1, $this->saved);
        $row = $this->saved[0];
        $this->assertNull($row['customer_id']);
        $this->assertSame(self::PRODUCT_ID, $row['product_id']);
        $this->assertSame('guest@example.com', $row['email']);
        $this->assertSame('Jane Guest', $row['customer_name']);
        $this->assertSame(1, $row['store_id']);
        $this->assertSame(StockAlert::STATUS_ACTIVE, $row['status']);
        $this->assertSame([5, 900], $session->values[Stock::SESSION_ALERT_IDS]);
    }

    public function testLoggedInSubscriptionUsesAccountDetails(): void
    {
        $session = $this->loggedInSession();
        $this->controller(
            ['product_id' => self::PRODUCT_ID, 'email' => 'spoof@example.com', 'customer_name' => 'Spoof'],
            ['session' => $session]
        )->execute();

        $this->assertTrue($this->json['success']);
        $row = $this->saved[0];
        $this->assertSame(7, $row['customer_id']);
        $this->assertSame('member@example.com', $row['email']);
        $this->assertSame('Mia Park', $row['customer_name']);
        $this->assertArrayNotHasKey(Stock::SESSION_ALERT_IDS, $session->values);
    }

    public function testSaveFailureReturnsGenericError(): void
    {
        $this->controller($this->guestParams(), ['saveFailure' => new \RuntimeException('db gone')])->execute();

        $this->assertError('Unable to subscribe to stock alerts. Please try again later.');
    }
}
