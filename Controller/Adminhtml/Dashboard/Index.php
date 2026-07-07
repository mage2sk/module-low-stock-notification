<?php
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::dashboard';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_LowStockNotification::dashboard');
        $resultPage->getConfig()->getTitle()->prepend(__('Stock Alerts Dashboard'));

        return $resultPage;
    }
}
