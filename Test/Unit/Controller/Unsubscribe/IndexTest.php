<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Unsubscribe;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Controller\Unsubscribe\Index;
use Panth\LowStockNotification\Model\RateLimiter;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IndexTest extends TestCase
{
    use BuildsAlerts;

    private array $messages = [];
    private ?string $redirectUrl = null;
    private array $collections = [];
    private array $saved = [];
    private array $logged = [];

    /**
     * @param array $queue collections handed out by the factory, in order
     */
    private function controller(array $params, array $queue, bool $allowed = true, ?\Exception $saveFailure = null): Index
    {
        $this->messages = ['success' => [], 'error' => []];
        $this->redirectUrl = null;
        $this->collections = [];
        $this->saved = [];
        $this->logged = [];

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setUrl')->willReturnCallback(function ($url) use (&$redirect) {
            $this->redirectUrl = $url;
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $messages = $this->createStub(ManagerInterface::class);
        $messages->method('addErrorMessage')->willReturnCallback(function ($m) use (&$messages) {
            $this->messages['error'][] = (string)$m;
            return $messages;
        });
        $messages->method('addSuccessMessage')->willReturnCallback(function ($m) use (&$messages) {
            $this->messages['success'][] = (string)$m;
            return $messages;
        });

        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnCallback(function () use (&$queue) {
            $collection = array_shift($queue) ?? new FakeAlertCollection();
            $this->collections[] = $collection;
            return $collection;
        });

        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('save')->willReturnCallback(function ($alert) use ($saveFailure, &$resource) {
            if ($saveFailure) {
                throw $saveFailure;
            }
            $this->saved[] = [(int)$alert->getId(), $alert->getStatus()];
            return $resource;
        });

        $limiter = $this->createStub(RateLimiter::class);
        $limiter->method('isAllowed')->willReturn($allowed);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function ($m) {
            $this->logged[] = $m;
        });

        return new Index($request, $redirectFactory, $messages, $factory, $resource, $limiter, $storeManager, $logger);
    }

    private function lookup(?StockAlert $alert): FakeAlertCollection
    {
        return new FakeAlertCollection([], null, $alert ?? $this->alert());
    }

    private function storedAlert(): StockAlert
    {
        return $this->alert([
            'alert_id' => 4,
            'email' => 'buyer@example.com',
            'store_id' => '2',
            'unsubscribe_token' => 'secret-token',
        ]);
    }

    private const INVALID = 'This unsubscribe link is invalid or has expired.';

    public function testRateLimitedRequestIsRejectedBeforeLookup(): void
    {
        $this->controller(['id' => 4, 'token' => 'secret-token'], [], false)->execute();

        $this->assertSame(['Too many requests. Please try again later.'], $this->messages['error']);
        $this->assertSame('https://shop.test/', $this->redirectUrl);
        $this->assertSame([], $this->collections);
    }

    public function testMissingTokenIsInvalidWithoutLookup(): void
    {
        $this->controller(['id' => 4], [])->execute();

        $this->assertSame([self::INVALID], $this->messages['error']);
        $this->assertSame([], $this->collections);
    }

    public function testMissingIdIsInvalid(): void
    {
        $this->controller(['token' => 'secret-token'], [])->execute();

        $this->assertSame([self::INVALID], $this->messages['error']);
    }

    public function testUnknownAlertIsInvalid(): void
    {
        $this->controller(['id' => 4, 'token' => 'secret-token'], [$this->lookup(null)])->execute();

        $this->assertSame([self::INVALID], $this->messages['error']);
        $this->assertSame([], $this->saved);
    }

    public function testWrongTokenIsInvalid(): void
    {
        $this->controller(['id' => 4, 'token' => 'guess'], [$this->lookup($this->storedAlert())])->execute();

        $this->assertSame([self::INVALID], $this->messages['error']);
        $this->assertSame([], $this->saved);
        $this->assertCount(1, $this->collections);
    }

    public function testAlertWithoutStoredTokenCannotBeUnsubscribedWithEmptyMatch(): void
    {
        $alert = $this->alert(['alert_id' => 4, 'email' => 'buyer@example.com', 'unsubscribe_token' => '']);
        $this->controller(['id' => 4, 'token' => 'x'], [$this->lookup($alert)])->execute();

        $this->assertSame([self::INVALID], $this->messages['error']);
    }

    public function testValidTokenCancelsAllActiveAlertsForEmailAndStore(): void
    {
        $active = new FakeAlertCollection([
            $this->alert(['alert_id' => 4, 'status' => StockAlert::STATUS_ACTIVE]),
            $this->alert(['alert_id' => 6, 'status' => StockAlert::STATUS_ACTIVE]),
        ]);
        $lookup = $this->lookup($this->storedAlert());
        $this->controller(['id' => '4', 'token' => 'secret-token'], [$lookup, $active])->execute();

        $this->assertSame(4, $lookup->filterValue('alert_id'));
        $this->assertSame(1, $lookup->pageSize);
        $this->assertSame('buyer@example.com', $active->filterValue('email'));
        $this->assertSame(2, $active->filterValue('store_id'));
        $this->assertSame(StockAlert::STATUS_ACTIVE, $active->filterValue('status'));
        $this->assertSame(
            [[4, StockAlert::STATUS_CANCELLED], [6, StockAlert::STATUS_CANCELLED]],
            $this->saved
        );
        $this->assertSame(['You have been unsubscribed from back-in-stock alerts.'], $this->messages['success']);
        $this->assertSame('https://shop.test/', $this->redirectUrl);
    }

    public function testSaveFailureIsLoggedAndReported(): void
    {
        $active = new FakeAlertCollection([$this->alert(['alert_id' => 4])]);
        $this->controller(
            ['id' => 4, 'token' => 'secret-token'],
            [$this->lookup($this->storedAlert()), $active],
            true,
            new \RuntimeException('deadlock')
        )->execute();

        $this->assertSame(['Unable to update your subscription. Please try again later.'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame(['LowStockNotification: token unsubscribe failed for alert #4: deadlock'], $this->logged);
    }
}
