<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Shared wiring for admin controller tests: records redirects and flash messages.
 */
abstract class AbstractControllerTestCase extends TestCase
{
    protected array $redirect = [];
    protected array $messages = ['success' => [], 'error' => [], 'warning' => [], 'notice' => []];
    protected Http $request;
    protected ResultFactory $resultFactory;

    protected function buildContext(array $params = [], array $post = [], array $files = []): Context
    {
        $this->redirect = [];
        $this->messages = ['success' => [], 'error' => [], 'warning' => [], 'notice' => []];

        $this->request = $this->createStub(Http::class);
        $this->request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => $params[$key] ?? $default
        );
        $this->request->method('getPostValue')->willReturn($post);
        $this->request->method('getFiles')->willReturnCallback(
            static fn($key = null) => $files[$key] ?? null
        );

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(
            function ($path, $params = []) use (&$redirect) {
                $this->redirect = ['path' => $path, 'params' => $params];
                return $redirect;
            }
        );
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $messageManager = $this->createStub(ManagerInterface::class);
        foreach (['success', 'error', 'warning', 'notice'] as $type) {
            $method = 'add' . ucfirst($type) . 'Message';
            $messageManager->method($method)->willReturnCallback(
                function ($message) use ($type, &$messageManager) {
                    $this->messages[$type][] = (string)$message;
                    return $messageManager;
                }
            );
        }

        $this->resultFactory = $this->createStub(ResultFactory::class);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getResultFactory')->willReturnCallback(fn() => $this->resultFactory);

        return $context;
    }
}
