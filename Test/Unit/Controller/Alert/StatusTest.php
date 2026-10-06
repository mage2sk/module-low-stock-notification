<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Alert;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Panth\LowStockNotification\Controller\Alert\Status;
use Panth\LowStockNotification\Controller\Alert\Stock;
use Panth\LowStockNotification\Helper\Data;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use Panth\LowStockNotification\Test\Unit\Fixture\CustomerSessionDouble;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    use BuildsAlerts;

    private array $json = [];
    private array $headers = [];
    private ?FakeAlertCollection $collection = null;
    private int $factoryCalls = 0;

    private function controller(array $params, array $options = []): Status
    {
        $this->json = [];
        $this->headers = [];
        $this->factoryCalls = 0;
        $options += [
            'enabled' => true,
            'session' => new CustomerSessionDouble(),
            'alerts' => [],
            'failure' => null,
        ];

        $result = $this->createStub(Json::class);
        $result->method('setData')->willReturnCallback(function ($data) use (&$result) {
            $this->json = $data;
            return $result;
        });
        $result->method('setHeader')->willReturnCallback(function ($name, $value) use (&$result) {
            $this->headers[$name] = $value;
            return $result;
        });
        $jsonFactory = $this->createStub(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($result);

        $this->collection = new FakeAlertCollection($options['alerts']);
        $factory = $this->createStub(StockAlertFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($options) {
            $this->factoryCalls++;
            if ($options['failure']) {
                throw $options['failure'];
            }
            return $this->alertWithCollection($this->collection);
        });

        $helper = $this->createStub(Data::class);
        $helper->method('isStockAlertEnabled')->willReturn($options['enabled']);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);

        return new Status($jsonFactory, $options['session'], $factory, $helper, $request);
    }

    private function member(): CustomerSessionDouble
    {
        return new CustomerSessionDouble(3, new DataObject(['email' => 'member@example.com']));
    }

    public function testDisabledModuleReportsNotSubscribed(): void
    {
        $this->controller(['product_id' => 5], [
            'enabled' => false,
            'session' => $this->member(),
            'alerts' => [$this->alert(['alert_id' => 1])],
        ])->execute();

        $this->assertSame(['subscribed' => false], $this->json);
        $this->assertSame(0, $this->factoryCalls);
    }

    public function testMissingProductIdReportsNotSubscribed(): void
    {
        $this->controller([], ['session' => $this->member()])->execute();

        $this->assertSame(['subscribed' => false], $this->json);
        $this->assertSame(0, $this->factoryCalls);
    }

    public function testResponseIsNeverCached(): void
    {
        $this->controller(['product_id' => 5])->execute();

        $this->assertStringContainsString('no-store', $this->headers['Cache-Control'] ?? '');
    }

    public function testLoggedInCustomerWithActiveAlertIsSubscribed(): void
    {
        $this->controller(['product_id' => '5'], [
            'session' => $this->member(),
            'alerts' => [$this->alert(['alert_id' => 20])],
        ])->execute();

        $this->assertSame(['subscribed' => true], $this->json);
        $this->assertSame(5, $this->collection->filterValue('product_id'));
        $this->assertSame(StockAlert::STATUS_ACTIVE, $this->collection->filterValue('status'));
        $this->assertSame('member@example.com', $this->collection->filterValue('email'));
        $this->assertNull($this->collection->filterValue('alert_id'));
    }

    public function testLoggedInCustomerWithoutAlertIsNotSubscribed(): void
    {
        $this->controller(['product_id' => 5], ['session' => $this->member()])->execute();

        $this->assertSame(['subscribed' => false], $this->json);
        $this->assertSame(1, $this->factoryCalls);
    }

    public function testGuestWithoutSessionAlertsIsNotSubscribedAndNoQueryRuns(): void
    {
        $session = new CustomerSessionDouble(null, null, [Stock::SESSION_ALERT_IDS => ['0', 'x']]);
        $this->controller(['product_id' => 5, 'email' => 'someone@example.com'], [
            'session' => $session,
            'alerts' => [$this->alert(['alert_id' => 9])],
        ])->execute();

        $this->assertSame(['subscribed' => false], $this->json);
        $this->assertSame(0, $this->factoryCalls, 'A guest cannot probe other emails');
    }

    public function testGuestIsCheckedOnlyAgainstAlertsFromTheirSession(): void
    {
        $session = new CustomerSessionDouble(null, null, [Stock::SESSION_ALERT_IDS => ['11', 0, '12']]);
        $this->controller(['product_id' => 5, 'email' => 'other@example.com'], [
            'session' => $session,
            'alerts' => [$this->alert(['alert_id' => 11])],
        ])->execute();

        $this->assertSame(['subscribed' => true], $this->json);
        $this->assertSame(['in' => [11, 12]], $this->collection->filterValue('alert_id'));
        $this->assertNull($this->collection->filterValue('email'));
    }

    public function testLookupFailureReportsNotSubscribed(): void
    {
        $this->controller(['product_id' => 5], [
            'session' => $this->member(),
            'failure' => new \RuntimeException('db down'),
        ])->execute();

        $this->assertSame(['subscribed' => false], $this->json);
    }
}
