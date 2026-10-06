<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Alert;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Controller\Alert\Stock;
use Panth\LowStockNotification\Controller\Alert\Unstock;
use Panth\LowStockNotification\Helper\Data;
use Panth\LowStockNotification\Model\RateLimiter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use Panth\LowStockNotification\Test\Unit\Fixture\CustomerSessionDouble;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;
use PHPUnit\Framework\TestCase;

class UnstockTest extends TestCase
{
    use BuildsAlerts;

    private array $json = [];
    private ?int $httpCode = null;
    private array $deleted = [];
    private ?FakeAlertCollection $collection = null;
    private int $factoryCalls = 0;

    private function controller(array $params, array $options = []): Unstock
    {
        $this->json = [];
        $this->httpCode = null;
        $this->deleted = [];
        $this->factoryCalls = 0;
        $options += [
            'enabled' => true,
            'allowed' => true,
            'session' => new CustomerSessionDouble(),
            'alerts' => [],
            'deleteFailure' => null,
        ];

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

        $this->collection = new FakeAlertCollection($options['alerts']);
        $factory = $this->createStub(StockAlertFactory::class);
        $factory->method('create')->willReturnCallback(function () {
            $this->factoryCalls++;
            return $this->alertWithCollection($this->collection);
        });

        $helper = $this->createStub(Data::class);
        $helper->method('isStockAlertEnabled')->willReturn($options['enabled']);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);

        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('delete')->willReturnCallback(function ($alert) use ($options, &$resource) {
            if ($options['deleteFailure']) {
                throw $options['deleteFailure'];
            }
            $this->deleted[] = (int)$alert->getId();
            return $resource;
        });

        $limiter = $this->createStub(RateLimiter::class);
        $limiter->method('isAllowed')->willReturn($options['allowed']);

        return new Unstock(
            $jsonFactory,
            $options['session'],
            $this->createStub(StoreManagerInterface::class),
            $factory,
            $helper,
            $request,
            $resource,
            $limiter
        );
    }

    private function assertError(string $message): void
    {
        $this->assertTrue($this->json['error'] ?? false);
        $this->assertSame($message, (string)$this->json['message']);
        $this->assertSame([], $this->deleted);
    }

    public function testDisabledModuleRejects(): void
    {
        $this->controller(['product_id' => 5], ['enabled' => false])->execute();
        $this->assertError('Stock alerts are disabled.');
    }

    public function testRateLimitedRequestGets429(): void
    {
        $this->controller(['product_id' => 5], ['allowed' => false])->execute();

        $this->assertSame(429, $this->httpCode);
        $this->assertError('Too many requests. Please try again later.');
    }

    public function testMissingProductIdIsRejected(): void
    {
        $this->controller([])->execute();
        $this->assertError('Product ID is required.');
    }

    public function testGuestWithoutSessionAlertsCannotUnsubscribe(): void
    {
        $session = new CustomerSessionDouble(null, null, [Stock::SESSION_ALERT_IDS => ['0', 'x']]);
        $this->controller(['product_id' => 5], ['session' => $session])->execute();

        $this->assertError('No active stock alert found for this product.');
        $this->assertSame(0, $this->factoryCalls, 'No query is made without an identity');
    }

    public function testGuestOnlyDeletesAlertsCreatedInTheirSession(): void
    {
        $session = new CustomerSessionDouble(null, null, [Stock::SESSION_ALERT_IDS => ['11', 0, '12']]);
        $this->controller(['product_id' => '5'], [
            'session' => $session,
            'alerts' => [$this->alert(['alert_id' => 11]), $this->alert(['alert_id' => 12])],
        ])->execute();

        $this->assertTrue($this->json['success']);
        $this->assertSame([11, 12], $this->deleted);
        $this->assertSame(5, $this->collection->filterValue('product_id'));
        $this->assertSame(StockAlert::STATUS_ACTIVE, $this->collection->filterValue('status'));
        $this->assertSame(['in' => [11, 12]], $this->collection->filterValue('alert_id'));
        $this->assertNull($this->collection->filterValue('email'));
    }

    public function testLoggedInCustomerUnsubscribesByEmail(): void
    {
        $session = new CustomerSessionDouble(3, new DataObject(['email' => 'member@example.com']));
        $this->controller(['product_id' => 5], [
            'session' => $session,
            'alerts' => [$this->alert(['alert_id' => 20])],
        ])->execute();

        $this->assertTrue($this->json['success']);
        $this->assertSame([20], $this->deleted);
        $this->assertSame('member@example.com', $this->collection->filterValue('email'));
        $this->assertNull($this->collection->filterValue('alert_id'));
    }

    public function testNoMatchingAlertIsReported(): void
    {
        $session = new CustomerSessionDouble(3, new DataObject(['email' => 'member@example.com']));
        $this->controller(['product_id' => 5], ['session' => $session])->execute();

        $this->assertError('No active stock alert found for this product.');
    }

    public function testDeleteFailureReturnsGenericError(): void
    {
        $session = new CustomerSessionDouble(3, new DataObject(['email' => 'member@example.com']));
        $this->controller(['product_id' => 5], [
            'session' => $session,
            'alerts' => [$this->alert(['alert_id' => 20])],
            'deleteFailure' => new \RuntimeException('locked'),
        ])->execute();

        $this->assertError('Unable to unsubscribe from stock alerts. Please try again later.');
    }
}
