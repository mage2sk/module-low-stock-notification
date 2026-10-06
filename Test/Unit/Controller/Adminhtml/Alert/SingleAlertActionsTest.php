<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Adminhtml\Alert;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Panth\LowStockNotification\Controller\Adminhtml\Alert\Delete;
use Panth\LowStockNotification\Controller\Adminhtml\Alert\Send;
use Panth\LowStockNotification\Controller\Adminhtml\Alert\View;
use Panth\LowStockNotification\Model\EmailSender;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Model\StockAlertFactory;
use Panth\LowStockNotification\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;

class SingleAlertActionsTest extends AbstractControllerTestCase
{
    use BuildsAlerts;

    private array $deleted = [];
    private array $titles = [];
    private ?string $activeMenu = null;

    private function factory(): StockAlertFactory
    {
        $factory = $this->createStub(StockAlertFactory::class);
        $factory->method('create')->willReturnCallback(fn() => $this->alert());
        return $factory;
    }

    /**
     * @param array $rows alert rows keyed by id that "exist" in storage
     */
    private function resource(array $rows, ?\Exception $deleteFailure = null): StockAlertResource
    {
        $this->deleted = [];
        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('load')->willReturnCallback(function ($model, $id) use ($rows, &$resource) {
            if (isset($rows[$id])) {
                $model->setData($rows[$id] + ['alert_id' => $id]);
            }
            return $resource;
        });
        $resource->method('delete')->willReturnCallback(function ($model) use ($deleteFailure, &$resource) {
            if ($deleteFailure) {
                throw $deleteFailure;
            }
            $this->deleted[] = (int)$model->getId();
            return $resource;
        });
        return $resource;
    }

    public function testDeleteWithoutIdReportsNothingToDelete(): void
    {
        $controller = new Delete($this->buildContext(), $this->factory(), $this->resource([]));
        $controller->execute();

        $this->assertSame(["We can't find an alert to delete."], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testDeleteOfMissingAlertReportsIt(): void
    {
        $controller = new Delete($this->buildContext(['alert_id' => '8']), $this->factory(), $this->resource([]));
        $controller->execute();

        $this->assertSame(['This alert no longer exists.'], $this->messages['error']);
        $this->assertSame([], $this->deleted);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testDeleteRemovesExistingAlert(): void
    {
        $controller = new Delete(
            $this->buildContext(['alert_id' => '8']),
            $this->factory(),
            $this->resource([8 => ['status' => 1]])
        );
        $controller->execute();

        $this->assertSame([8], $this->deleted);
        $this->assertSame(['The alert has been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testDeleteFailureReturnsToAlertView(): void
    {
        $controller = new Delete(
            $this->buildContext(['alert_id' => '8']),
            $this->factory(),
            $this->resource([8 => ['status' => 1]], new \RuntimeException('Locked row'))
        );
        $controller->execute();

        $this->assertSame(['Locked row'], $this->messages['error']);
        $this->assertSame(['path' => '*/*/view', 'params' => ['alert_id' => 8]], $this->redirect);
    }

    private function sender(?\Exception $failure = null, array &$sent = []): EmailSender
    {
        $sender = $this->createStub(EmailSender::class);
        $sender->method('sendAlertEmail')->willReturnCallback(function ($alert) use ($failure, &$sent) {
            if ($failure) {
                throw $failure;
            }
            $sent[] = (int)$alert->getId();
            return true;
        });
        return $sender;
    }

    public function testSendWithoutIdReportsNothingToSend(): void
    {
        $controller = new Send($this->buildContext(), $this->factory(), $this->resource([]), $this->sender());
        $controller->execute();

        $this->assertSame(["We can't find an alert to send."], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testSendOfMissingAlertReportsIt(): void
    {
        $controller = new Send(
            $this->buildContext(['alert_id' => 3]),
            $this->factory(),
            $this->resource([]),
            $this->sender()
        );
        $controller->execute();

        $this->assertSame(['This alert no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testOnlyPendingAlertsCanBeSent(): void
    {
        $sent = [];
        $controller = new Send(
            $this->buildContext(['alert_id' => 3]),
            $this->factory(),
            $this->resource([3 => ['status' => StockAlert::STATUS_SENT]]),
            $this->sender(null, $sent)
        );
        $controller->execute();

        $this->assertSame([], $sent);
        $this->assertSame(['Only pending alerts can be sent.'], $this->messages['error']);
        $this->assertSame(['path' => '*/*/view', 'params' => ['alert_id' => 3]], $this->redirect);
    }

    public function testPendingAlertIsSent(): void
    {
        $sent = [];
        $controller = new Send(
            $this->buildContext(['alert_id' => 3]),
            $this->factory(),
            $this->resource([3 => ['status' => (string)StockAlert::STATUS_ACTIVE]]),
            $this->sender(null, $sent)
        );
        $controller->execute();

        $this->assertSame([3], $sent);
        $this->assertSame(['The alert email has been sent.'], $this->messages['success']);
        $this->assertSame(['path' => '*/*/view', 'params' => ['alert_id' => 3]], $this->redirect);
    }

    public function testSendFailureShowsErrorOnView(): void
    {
        $controller = new Send(
            $this->buildContext(['alert_id' => 3]),
            $this->factory(),
            $this->resource([3 => ['status' => StockAlert::STATUS_ACTIVE]]),
            $this->sender(new \RuntimeException('SMTP down'))
        );
        $controller->execute();

        $this->assertSame(['SMTP down'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame(['path' => '*/*/view', 'params' => ['alert_id' => 3]], $this->redirect);
    }

    private function pageFactory(): PageFactory
    {
        $this->titles = [];
        $this->activeMenu = null;
        $title = $this->createStub(Title::class);
        $title->method('prepend')->willReturnCallback(function ($text) {
            $this->titles[] = (string)$text;
        });
        $config = $this->createStub(PageConfig::class);
        $config->method('getTitle')->willReturn($title);
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($config);
        $page->method('setActiveMenu')->willReturnCallback(function ($menu) use (&$page) {
            $this->activeMenu = $menu;
            return $page;
        });
        $factory = $this->createStub(PageFactory::class);
        $factory->method('create')->willReturn($page);
        return $factory;
    }

    public function testViewOfMissingAlertRedirectsToGrid(): void
    {
        $controller = new View(
            $this->buildContext(['alert_id' => 11]),
            $this->pageFactory(),
            $this->factory(),
            $this->resource([])
        );
        $controller->execute();

        $this->assertSame(['This alert no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
        $this->assertSame([], $this->titles);
    }

    public function testViewOfExistingAlertRendersTitledPage(): void
    {
        $controller = new View(
            $this->buildContext(['alert_id' => 11]),
            $this->pageFactory(),
            $this->factory(),
            $this->resource([11 => ['status' => 1]])
        );
        $result = $controller->execute();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertSame(['View Alert #11'], $this->titles);
        $this->assertSame('Panth_LowStockNotification::alerts', $this->activeMenu);
        $this->assertSame([], $this->redirect);
    }
}
