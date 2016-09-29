<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Reports\Fraud;

/**
 * Sage Pay fraud report
 */
class Index extends \Magento\Backend\App\Action
{

    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Backend::admin';//TODO

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_initAction();
        $this->_view->renderLayout();
    }

    /**
     * Initialize titles, navigation
     *
     * @return $this
     */
    // @codingStandardsIgnoreStart
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Ebizmarts_SagePaySuite::report_sagepaysuite_fraud_report'
        )->_addBreadcrumb(
            __('Reports'),
            __('Reports')
        )->_addBreadcrumb(
            __('Sage Pay'),
            __('Sage Pay')
        )->_addBreadcrumb(
            __('Fraud'),
            __('Fraud')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Sage Pay Fraud'));
        return $this;
    }
    // @codingStandardsIgnoreEnd
}
