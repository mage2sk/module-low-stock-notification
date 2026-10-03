<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use PHPUnit\Framework\TestCase;

class StockAlertTest extends TestCase
{
    use BuildsAlerts;

    private function beforeSave($alert): void
    {
        $resource = new StockAlertResource($this->createStub(Context::class));
        $method = new \ReflectionMethod(StockAlertResource::class, '_beforeSave');
        $method->invoke($resource, $alert);
    }

    public function testIdFieldIsAlertId(): void
    {
        $resource = new StockAlertResource($this->createStub(Context::class));

        $this->assertSame('alert_id', $resource->getIdFieldName());
    }

    public function testMissingTokenIsGenerated(): void
    {
        $alert = $this->alert(['email' => 'a@example.com']);
        $this->beforeSave($alert);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $alert->getData('unsubscribe_token'));
    }

    public function testGeneratedTokensAreUnique(): void
    {
        $first = $this->alert(['unsubscribe_token' => '']);
        $second = $this->alert();
        $this->beforeSave($first);
        $this->beforeSave($second);

        $this->assertNotSame($first->getData('unsubscribe_token'), $second->getData('unsubscribe_token'));
    }

    public function testExistingTokenIsPreserved(): void
    {
        $alert = $this->alert(['unsubscribe_token' => 'keepme']);
        $this->beforeSave($alert);

        $this->assertSame('keepme', $alert->getData('unsubscribe_token'));
    }
}
