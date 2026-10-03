<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LowStockNotification\Model\EmailSender;
use Panth\LowStockNotification\Model\ResourceModel\StockAlert as StockAlertResource;
use Panth\LowStockNotification\Model\StockAlert;
use Panth\LowStockNotification\Test\Unit\Fixture\BuildsAlerts;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EmailSenderTest extends TestCase
{
    use BuildsAlerts;

    private array $built = [];
    private array $urlCall = [];
    private int $saves = 0;
    private array $errors = [];
    private ?\Exception $sendFailure = null;
    private ?ProductRepositoryInterface $productRepository = null;

    private function sender(array $config = []): EmailSender
    {
        $this->built = [];
        $this->saves = 0;
        $this->errors = [];

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(2);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn($path) => $config[$path] ?? null);

        $transport = $this->createStub(TransportInterface::class);
        $transport->method('sendMessage')->willReturnCallback(function () {
            if ($this->sendFailure) {
                throw $this->sendFailure;
            }
            $this->built['sent'] = ($this->built['sent'] ?? 0) + 1;
        });

        $builder = $this->createStub(TransportBuilder::class);
        foreach (['setTemplateIdentifier', 'setTemplateOptions', 'setTemplateVars', 'setFromByScope', 'addTo']
            as $method) {
            $builder->method($method)->willReturnCallback(function (...$args) use ($method, &$builder) {
                $this->built[$method] = $args;
                return $builder;
            });
        }
        $builder->method('getTransport')->willReturn($transport);

        $resource = $this->createStub(StockAlertResource::class);
        $resource->method('save')->willReturnCallback(function ($alert) use (&$resource) {
            $this->saves++;
            if ((string)$alert->getData('unsubscribe_token') === '') {
                $alert->setData('unsubscribe_token', 'generated-token');
            }
            return $resource;
        });

        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtDate')->willReturn('2026-10-03 10:00:00');

        $priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $priceCurrency->method('convertAndFormat')->willReturnCallback(
            static fn($amount) => 'USD ' . number_format((float)$amount, 2)
        );

        $url = $this->createStub(Url::class);
        $url->method('setScope')->willReturnSelf();
        $url->method('getUrl')->willReturnCallback(function ($route, $params) {
            $this->urlCall = [$route, $params];
            return 'https://shop.test/' . $route . '?token=' . $params['token'];
        });

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function ($message) {
            $this->errors[] = $message;
        });

        return new EmailSender(
            $this->productRepository ?? $this->createStub(ProductRepositoryInterface::class),
            $builder,
            $storeManager,
            $scopeConfig,
            $logger,
            $resource,
            $dateTime,
            $priceCurrency,
            $url
        );
    }

    private function product(float $final, float $price = 50.0): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getFinalPrice')->willReturn($final);
        $product->method('getPrice')->willReturn($price);
        $product->method('getName')->willReturn('Trail Shoe');
        $product->method('getProductUrl')->willReturn('https://shop.test/trail-shoe.html');
        $product->method('getId')->willReturn(12);
        return $product;
    }

    private function activeAlert(array $extra = []): StockAlert
    {
        return $this->alert($extra + [
            'alert_id' => 5,
            'product_id' => 12,
            'store_id' => 2,
            'email' => 'buyer@example.com',
            'customer_name' => 'Ann Lee',
            'status' => StockAlert::STATUS_ACTIVE,
            'unsubscribe_token' => 'abc123',
        ]);
    }

    public function testSendsEmailAndMarksAlertSent(): void
    {
        $sender = $this->sender([EmailSender::XML_PATH_EMAIL_SENDER => 'sales', EmailSender::XML_PATH_EMAIL_TEMPLATE => 'custom_tpl']);
        $alert = $this->activeAlert();

        $this->assertTrue($sender->sendAlertEmail($alert, $this->product(39.5)));

        $this->assertSame(1, $this->built['sent']);
        $this->assertSame(['custom_tpl'], $this->built['setTemplateIdentifier']);
        $this->assertSame([['area' => 'frontend', 'store' => 2]], $this->built['setTemplateOptions']);
        $this->assertSame(['sales', 2], $this->built['setFromByScope']);
        $this->assertSame('buyer@example.com', $this->built['addTo'][0]);

        $vars = $this->built['setTemplateVars'][0];
        $this->assertSame('Ann Lee', $vars['customer_name']);
        $this->assertSame('Trail Shoe', $vars['product_name']);
        $this->assertSame('https://shop.test/trail-shoe.html', $vars['product_url']);
        $this->assertSame('USD 39.50', $vars['product_price']);
        $this->assertSame('https://shop.test/lowstocknotification/unsubscribe/index?token=abc123', $vars['unsubscribe_url']);
        $this->assertSame(
            ['lowstocknotification/unsubscribe/index', ['id' => 5, 'token' => 'abc123', '_nosid' => true]],
            $this->urlCall
        );

        $this->assertSame(StockAlert::STATUS_SENT, $alert->getStatus());
        $this->assertSame('2026-10-03 10:00:00', $alert->getSentAt());
        $this->assertSame(1, $this->saves);
    }

    public function testDefaultsAreUsedWhenConfigAndNameAreEmpty(): void
    {
        $sender = $this->sender();
        $sender->sendAlertEmail($this->activeAlert(['customer_name' => '']), $this->product(0.0, 25.0));

        $this->assertSame([EmailSender::DEFAULT_TEMPLATE], $this->built['setTemplateIdentifier']);
        $this->assertSame(['general', 2], $this->built['setFromByScope']);
        $vars = $this->built['setTemplateVars'][0];
        $this->assertSame('Valued Customer', $vars['customer_name']);
        $this->assertSame('USD 25.00', $vars['product_price'], 'Falls back to the regular price');
    }

    public function testMissingTokenIsPersistedBeforeBuildingUnsubscribeLink(): void
    {
        $sender = $this->sender();
        $sender->sendAlertEmail($this->activeAlert(['unsubscribe_token' => null]), $this->product(10.0));

        $this->assertSame('generated-token', $this->urlCall[1]['token']);
        $this->assertSame(2, $this->saves, 'One save for the token, one for the sent status');
    }

    public function testAlreadySentAlertIsNotSavedAgain(): void
    {
        $sender = $this->sender();
        $alert = $this->activeAlert(['status' => StockAlert::STATUS_SENT, 'sent_at' => '2026-01-01 00:00:00']);

        $sender->sendAlertEmail($alert, $this->product(10.0));

        $this->assertSame(1, $this->built['sent']);
        $this->assertSame(0, $this->saves);
        $this->assertSame('2026-01-01 00:00:00', $alert->getSentAt());
    }

    public function testProductIsLoadedForAlertStoreWhenNotGiven(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getById')
            ->with(12, false, 2)
            ->willReturn($this->product(10.0));
        $this->productRepository = $repository;

        $this->assertTrue($this->sender()->sendAlertEmail($this->activeAlert()));
        $this->assertSame(1, $this->built['sent']);
    }

    public function testTransportFailureIsLoggedRethrownAndLeavesAlertPending(): void
    {
        $this->sendFailure = new \RuntimeException('SMTP down');
        $sender = $this->sender();
        $alert = $this->activeAlert();

        try {
            $sender->sendAlertEmail($alert, $this->product(10.0));
            $this->fail('Expected the transport exception to be rethrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('SMTP down', $e->getMessage());
        }

        $this->assertSame(['Failed to send stock alert email: SMTP down'], $this->errors);
        $this->assertSame(StockAlert::STATUS_ACTIVE, $alert->getStatus());
        $this->assertSame(0, $this->saves);
    }
}
