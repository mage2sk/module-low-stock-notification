<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Fixture;

use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Registry;
use Panth\LowStockNotification\Model\StockAlert;

/**
 * Builds real StockAlert models backed by a stub resource.
 */
trait BuildsAlerts
{
    protected function alert(array $data = []): StockAlert
    {
        $resource = $this->createStub(AbstractDb::class);
        $resource->method('getIdFieldName')->willReturn('alert_id');

        $alert = new StockAlert(
            $this->createStub(ModelContext::class),
            $this->createStub(Registry::class),
            $resource
        );
        $alert->setData($data);
        return $alert;
    }

    /**
     * A real alert model whose getCollection() returns the given collection.
     */
    protected function alertWithCollection($collection, array $data = []): StockAlert
    {
        $resource = $this->createStub(AbstractDb::class);
        $resource->method('getIdFieldName')->willReturn('alert_id');

        $alert = new class (
            $this->createStub(ModelContext::class),
            $this->createStub(Registry::class),
            $resource
        ) extends StockAlert {
            public $fakeCollection;

            public function getCollection()
            {
                return $this->fakeCollection;
            }
        };
        $alert->fakeCollection = $collection;
        $alert->setData($data);
        return $alert;
    }
}
