<?php
/**
 * Copyright © 2018 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Observer;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Api\Reporting;
use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Message\ManagerInterface;

class SystemConfigEdit implements ObserverInterface
{
    /**
     * @var Data
     */
    private $_suiteHelper;

    /**
     * @var ManagerInterface
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
     * @param ManagerInterface $messageManager
     * @param Reporting $reportingApi
     */
    public function __construct(
        Logger $suiteLogger,
        Config $suiteConfig,
        Data $suiteHelper,
        ManagerInterface $messageManager,
        Reporting $reportingApi
    ) {
        $this->_suiteHelper = $suiteHelper;
        $this->_messageManager = $messageManager;
        $this->_reportingApi = $reportingApi;
    }

    /**
     * Observer payment config section save to validate license and
     * check reporting api credentials.
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $section = $observer->getEvent()->getRequest()->getParam('section');
        if ($section == "payment") {
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
        } catch (ApiException $apiException) {
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
