<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Observer;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Api\Reporting;
use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Event\ObserverInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class SystemConfigEdit implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $_suiteLogger;

    /**
     * @var Config
     */
    private $_suiteConfig;

    /**
     * @var Data
     */
    private $_suiteHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     * @var Reporting
     */
    private $_reportingApi;

    /**
     * @param Logger $suiteLogger
     * @param Config $suiteConfig
     * @param Data $suiteHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param Reporting $reportingApi
     */
    public function __construct(
        Logger $suiteLogger,
        Config $suiteConfig,
        Data $suiteHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Reporting $reportingApi
    ) {
    
        $this->_suiteLogger = $suiteLogger;
        $this->_suiteConfig = $suiteConfig;
        $this->_suiteHelper = $suiteHelper;
        $this->_messageManager = $messageManager;
        $this->_reportingApi = $reportingApi;
    }

    /**
     * Checkout Cart index observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $section = $observer->getEvent()->getRequest()->getParam('section');
        if ($section == "payment") {

            /**
             * VALIDATE LICENSE
             */
            if (!$this->isLicenseKeyValid()) {
                $this->_messageManager->addError(__('Your Sage Pay Suite license is invalid.'));
            }

            $this->verifyReportingApiCredentialsByCallingVersion();
        }
    }

    private function verifyReportingApiCredentialsByCallingVersion()
    {
        try {
            $this->_reportingApi->getVersion();
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_messageManager->addError($apiException->getUserMessage());
        } catch (\Exception $e) {
            $this->_messageManager->addError(__('Can not establish connection with Sage Pay API.'));
        }
    }

    /**
     * @return bool
     */
    private function isLicenseKeyValid()
    {
        return $this->_suiteHelper->verify();
    }
}
