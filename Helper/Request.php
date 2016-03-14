<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Request extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger
    )
    {
        $this->_config = $config;
        $this->_suiteLogger = $suiteLogger;
    }

    public function populateAddressInformation($quote)
    {

        $billing_address = $quote->getBillingAddress();
        $shipping_address = $quote->isVirtual() ? $billing_address : $quote->getShippingAddress();

        $data = array();

        $data['BillingSurname'] = substr($billing_address->getLastname(), 0, 20);
        $data['BillingFirstnames'] = substr($billing_address->getFirstname(), 0, 20);
        $data['BillingAddress1'] = substr($billing_address->getStreetLine(1), 0, 100);
        $data['BillingCity'] = substr($billing_address->getCity(), 0, 40);
        $data['BillingPostCode'] = substr($billing_address->getPostcode(), 0, 10);
        $data['BillingCountry'] = substr($billing_address->getCountryId(), 0, 2);

        //only send state if US due to Sage Pay 2 char restriction
        if ($data['BillingCountry'] == 'US') {
            $data['BillingState'] = substr($billing_address->getRegionCode(), 0, 2);
        }

        //not mandatory
//        $data['BillingAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);

        //mandatory
        $data['DeliverySurname'] = substr($shipping_address->getLastname(), 0, 20);
        $data['DeliveryFirstnames'] = substr($shipping_address->getFirstname(), 0, 20);
        $data['DeliveryAddress1'] = substr($shipping_address->getStreetLine(1), 0, 100);
        $data['DeliveryCity'] = substr($shipping_address->getCity(), 0, 40);
        $data['DeliveryPostCode'] = substr($shipping_address->getPostcode(), 0, 10);
        $data['DeliveryCountry'] = substr($shipping_address->getCountryId(), 0, 2);
        //only send state if US due to Sage Pay 2 char restriction
        if ($data['DeliveryCountry'] == 'US') {
            $data['DeliveryState'] = substr($shipping_address->getRegionCode(), 0, 2);
        }

        //not mandatory
//        $data['DeliveryAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);
//        $data['DeliveryState'] = $billing->getRegionCode();
//        $data['DeliveryPhone'] = $billing->getRegionCode();

        return $data;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param bool|false $isRestRequest
     * @return array
     */
    public function populatePaymentAmount($quote, $isRestRequest = false)
    {
        $currencyCode = $this->_config->getCurrencyCode();
        $amount = $quote->getBaseGrandTotal();

        switch ($this->_config->getCurrencyConfig()) {
            case Config::CURRENCY_SWITCHER:
                $amount = $quote->getGrandTotal();
                break;
        }

        $data = array();
        if ($isRestRequest) {
            $data["amount"] = $amount * 100;
            $data["currency"] = $currencyCode;
        } else {
            $data["Amount"] = number_format($amount, 2, '.', '');
            $data["Currency"] = $currencyCode;
        }


        return $data;
    }

    public function getOrderDescription($isMOTO = false)
    {
        return $isMOTO ? __("Online MOTO transaction.") : __("Online transaction.");
    }

}