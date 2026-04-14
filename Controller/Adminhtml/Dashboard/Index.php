<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Stock Alert Dashboard Controller
 */
declare(strict_types=1);

namespace Panth\LowStockNotification\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Panth_LowStockNotification::dashboard';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_LowStockNotification::dashboard');
        $resultPage->getConfig()->getTitle()->prepend(__('Stock Alerts Dashboard'));

        return $resultPage;
    }
}
