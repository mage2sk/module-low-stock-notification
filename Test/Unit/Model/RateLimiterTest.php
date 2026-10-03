<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Panth\LowStockNotification\Model\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private array $saved = [];
    private array $loadedKeys = [];

    private function limiter($limit, $window, string $ip, $stored = false): RateLimiter
    {
        $this->saved = [];
        $this->loadedKeys = [];
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('getValue')->willReturnCallback(
            static fn($path) => $path === RateLimiter::XML_PATH_LIMIT ? $limit : $window
        );
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('load')->willReturnCallback(function ($key) use ($stored) {
            $this->loadedKeys[] = $key;
            return $stored;
        });
        $cache->method('save')->willReturnCallback(function ($data, $key, $tags = [], $lifetime = null) {
            $this->saved[] = [$data, $key, $tags, $lifetime];
            return true;
        });
        $remote = $this->createStub(RemoteAddress::class);
        $remote->method('getRemoteAddress')->willReturn($ip);
        return new RateLimiter($cache, $config, $remote);
    }

    private function expectedKeys(string $action, string $ip, int $window, int $before, int $after): array
    {
        return array_values(array_unique([
            'panth_lowstocknotification_rl_' . sha1($action . '|' . $ip . '|' . (int)floor($before / $window)),
            'panth_lowstocknotification_rl_' . sha1($action . '|' . $ip . '|' . (int)floor($after / $window)),
        ]));
    }

    public function testDisabledLimitAlwaysAllowsWithoutTouchingCache(): void
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('getValue')->willReturn('0');
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('load');
        $cache->expects($this->never())->method('save');

        $limiter = new RateLimiter($cache, $config, $this->createStub(RemoteAddress::class));

        $this->assertTrue($limiter->isAllowed('subscribe'));
    }

    public function testFirstRequestIsAllowedAndCounted(): void
    {
        $before = time();
        $limiter = $this->limiter('3', '120', '10.0.0.1');
        $this->assertTrue($limiter->isAllowed('subscribe'));
        $after = time();

        $this->assertCount(1, $this->saved);
        [$data, $key, $tags, $lifetime] = $this->saved[0];
        $this->assertSame('1', $data);
        $this->assertSame([], $tags);
        $this->assertSame(120, $lifetime);
        $this->assertContains($key, $this->expectedKeys('subscribe', '10.0.0.1', 120, $before, $after));
    }

    public function testCounterIncrementsBelowLimit(): void
    {
        $limiter = $this->limiter('3', '60', '10.0.0.1', '2');

        $this->assertTrue($limiter->isAllowed('subscribe'));
        $this->assertSame('3', $this->saved[0][0]);
    }

    public function testRequestAtLimitIsRejectedWithoutSaving(): void
    {
        $limiter = $this->limiter('3', '60', '10.0.0.1', '3');

        $this->assertFalse($limiter->isAllowed('subscribe'));
        $this->assertSame([], $this->saved);
    }

    public function testInvalidWindowFallsBackToTenMinutes(): void
    {
        $limiter = $this->limiter('5', '-1', '10.0.0.1');

        $this->assertTrue($limiter->isAllowed('subscribe'));
        $this->assertSame(600, $this->saved[0][3]);
    }

    public function testMissingIpIsBucketedAsUnknown(): void
    {
        $before = time();
        $limiter = $this->limiter('5', '600', '');
        $limiter->isAllowed('unsubscribe');
        $after = time();

        $this->assertContains(
            $this->loadedKeys[0],
            $this->expectedKeys('unsubscribe', 'unknown', 600, $before, $after)
        );
    }

    public function testDifferentActionsUseSeparateCounters(): void
    {
        $limiter = $this->limiter('5', '600', '10.0.0.1');
        $limiter->isAllowed('subscribe');
        $limiter->isAllowed('unsubscribe');

        $this->assertCount(2, $this->loadedKeys);
        $this->assertNotSame($this->loadedKeys[0], $this->loadedKeys[1]);
    }
}
