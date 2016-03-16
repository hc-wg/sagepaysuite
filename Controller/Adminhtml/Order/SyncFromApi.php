<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Order;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class SyncFromApi extends \Magento\Backend\App\AbstractAction
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting
     */
    protected $_reportingApi;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger
    )
    {
        parent::__construct($context);
        $this->_reportingApi = $reportingApi;
        $this->_orderFactory = $orderFactory;
        $this->_suiteLogger = $suiteLogger;
    }

    public function execute()
    {
        try {

            //get order id
            if (!empty($this->getRequest()->getParam("order_id"))) {
                $order = $this->_orderFactory->create()->load($this->getRequest()->getParam("order_id"));
                $payment = $order->getPayment();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to sync from API: Invalid order id.'));
            }

            $transactionDetails = $this->_reportingApi->getTransactionDetails($payment->getLastTransId());

            $payment->setAdditionalInformation('vendorTxCode', (string)$transactionDetails->vendortxcode);
            $payment->setAdditionalInformation('statusDetail', (string)$transactionDetails->status);
            if (isset($transactionDetails->{'threedresult'})) {
                $payment->setAdditionalInformation('threeDStatus', (string)$transactionDetails->{'threedresult'});
            }
            $payment->save();

            $this->messageManager->addSuccess(__('Successfully synced from Sage Pay\'s API'));

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
            $this->messageManager->addError(__($apiException->getUserMessage()));

        } catch (\Exception $e)
        {
            $this->messageManager->addError(__('Something went wrong: ' . $e->getMessage()));
        }

        if(!empty($order)){
            $this->_redirect($this->_backendUrl->getUrl('sales/order/view/',array('order_id'=>$order->getId())));
        }else{
            $this->_redirect($this->_backendUrl->getUrl('sales/order/index/',array()));
        }
    }
}
