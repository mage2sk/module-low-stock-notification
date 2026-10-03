<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

/**
 * The backend Template constructor pulls optional helpers from the static object
 * manager, so a stub is installed for the test and the previous one restored after.
 */
abstract class BackendBlockTestCase extends TestCase
{
    private $previousObjectManager;
    protected array $urlCalls = [];

    protected function setUp(): void
    {
        $property = new \ReflectionProperty(ObjectManager::class, '_instance');
        $this->previousObjectManager = $property->getValue();
        ObjectManager::setInstance($this->createStub(ObjectManagerInterface::class));
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, $this->previousObjectManager);
    }

    protected function context(array $params = []): Context
    {
        $this->urlCalls = [];
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(function ($route, $routeParams = []) {
            $this->urlCalls[] = [$route, $routeParams];
            return $route . ($routeParams ? '?' . http_build_query($routeParams) : '');
        });
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        return $context;
    }
}
