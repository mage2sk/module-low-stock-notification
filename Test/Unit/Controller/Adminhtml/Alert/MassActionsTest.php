<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Adminhtml\Alert;

use Magento\Ui\Component\MassAction\Filter;
use Panth\LowStockNotification\Controller\Adminhtml\Alert\MassDelete;
use Panth\LowStockNotification\Controller\Adminhtml\Alert\MassSend;
use Panth\LowStockNotification\Model\EmailSender;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert\CollectionFactory;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use Panth\LowStockNotification\Test\Unit\Fixture\FakeAlertCollection;
use Psr\Log\LoggerInterface;

class MassActionsTest extends AbstractControllerTestCase
{
    use BuildsAlerts;

    private function filterReturning(FakeAlertCollection $selected): Filter
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturn($selected);
        return $filter;
    }

    private function collectionFactory(): CollectionFactory
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn(new FakeAlertCollection());
        return $factory;
    }

    public function testMassDeleteRemovesEverySelectedAlert(): void
    {
        $selected = new FakeAlertCollection([
            $this->alert(['alert_id' => 1]),
            $this->alert(['alert_id' => 2]),
            $this->alert(['alert_id' => 3]),
        ]);
        $deleted = [];
        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('delete')->willReturnCallback(function ($alert) use (&$deleted, &$resource) {
            $deleted[] = (int)$alert->getId();
            return $resource;
        });

        $controller = new MassDelete(
            $this->buildContext(),
            $this->filterReturning($selected),
            $this->collectionFactory(),
            $resource
        );
        $controller->execute();

        $this->assertSame([1, 2, 3], $deleted);
        $this->assertSame(['A total of 3 record(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    private function massSend(array $alerts, array $failingIds, array &$sent, array &$logged): MassSend
    {
        $sender = $this->createStub(EmailSender::class);
        $sender->method('sendAlertEmail')->willReturnCallback(function ($alert) use ($failingIds, &$sent) {
            if (in_array((int)$alert->getId(), $failingIds, true)) {
                throw new \RuntimeException('bounce ' . $alert->getId());
            }
            $sent[] = (int)$alert->getId();
            return true;
        });
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function ($message) use (&$logged) {
            $logged[] = $message;
        });

        return new MassSend(
            $this->buildContext(),
            $this->filterReturning(new FakeAlertCollection($alerts)),
            $this->collectionFactory(),
            $sender,
            $logger
        );
    }

    public function testMassSendReportsSentSkippedAndFailed(): void
    {
        $sent = [];
        $logged = [];
        $this->massSend([
            $this->alert(['alert_id' => 1, 'status' => StockAlert::STATUS_ACTIVE]),
            $this->alert(['alert_id' => 2, 'status' => StockAlert::STATUS_SENT]),
            $this->alert(['alert_id' => 3, 'status' => '1']),
            $this->alert(['alert_id' => 4, 'status' => StockAlert::STATUS_CANCELLED]),
        ], [3], $sent, $logged)->execute();

        $this->assertSame([1], $sent);
        $this->assertSame(['A total of 1 email(s) have been sent.'], $this->messages['success']);
        $this->assertSame(['2 alert(s) were skipped because they are not pending.'], $this->messages['notice']);
        $this->assertSame(['Failed to send 1 email(s).'], $this->messages['error']);
        $this->assertSame(['Failed to send alert email: bounce 3'], $logged);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testMassSendWithOnlyPendingAlertsShowsOnlySuccess(): void
    {
        $sent = [];
        $logged = [];
        $this->massSend([
            $this->alert(['alert_id' => 5, 'status' => StockAlert::STATUS_ACTIVE]),
            $this->alert(['alert_id' => 6, 'status' => StockAlert::STATUS_ACTIVE]),
        ], [], $sent, $logged)->execute();

        $this->assertSame([5, 6], $sent);
        $this->assertSame(['A total of 2 email(s) have been sent.'], $this->messages['success']);
        $this->assertSame([], $this->messages['notice']);
        $this->assertSame([], $this->messages['error']);
    }

    public function testMassSendWithEmptySelectionAddsNoMessages(): void
    {
        $sent = [];
        $logged = [];
        $this->massSend([], [], $sent, $logged)->execute();

        $this->assertSame(['success' => [], 'error' => [], 'warning' => [], 'notice' => []], $this->messages);
        $this->assertSame('*/*/', $this->redirect['path']);
    }
}
