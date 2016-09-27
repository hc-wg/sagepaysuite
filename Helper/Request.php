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

    /**
     * @param Config $config
     * @param Logger $suiteLogger
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger
    ) {
    
        $this->_config = $config;
        $this->_suiteLogger = $suiteLogger;
    }

    public function populateAddressInformation($quote)
    {

        $billing_address = $quote->getBillingAddress();
        $shipping_address = $quote->isVirtual() ? $billing_address : $quote->getShippingAddress();

        $data = [];

        //customer email
        $data["CustomerEMail"] = $billing_address->getEmail();

        $data['BillingSurname']    = substr($billing_address->getLastname(), 0, 20);
        $data['BillingFirstnames'] = substr($billing_address->getFirstname(), 0, 20);
        $data['BillingAddress1']   = substr($billing_address->getStreetLine(1), 0, 100);
        $data['BillingAddress2']   = substr($billing_address->getStreetLine(2), 0, 100);
        $data['BillingCity']       = substr($billing_address->getCity(), 0, 40);
        $data['BillingPostCode']   = substr($billing_address->getPostcode(), 0, 10);
        $data['BillingCountry']    = substr($billing_address->getCountryId(), 0, 2);

        //only send state if US due to Sage Pay 2 char restriction
        if ($data['BillingCountry'] == 'US') {
            $data['BillingState'] = substr($billing_address->getRegionCode(), 0, 2);
        }

        $data['BillingPhone'] = substr($billing_address->getTelephone(), 0, 20);

        //not mandatory
//        $data['BillingAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);

        //mandatory
        $data['DeliverySurname']    = substr($shipping_address->getLastname(), 0, 20);
        $data['DeliveryFirstnames'] = substr($shipping_address->getFirstname(), 0, 20);
        $data['DeliveryAddress1']   = substr($shipping_address->getStreetLine(1), 0, 100);
        $data['DeliveryAddress2']   = substr($shipping_address->getStreetLine(2), 0, 100);
        $data['DeliveryCity']       = substr($shipping_address->getCity(), 0, 40);
        $data['DeliveryPostCode']   = substr($shipping_address->getPostcode(), 0, 10);
        $data['DeliveryCountry']    = substr($shipping_address->getCountryId(), 0, 2);
        //only send state if US due to Sage Pay 2 char restriction
        if ($data['DeliveryCountry'] == 'US') {
            $data['DeliveryState'] = substr($shipping_address->getRegionCode(), 0, 2);
        }

        $data['DeliveryPhone'] = substr($shipping_address->getTelephone(), 0, 20);

        //not mandatory
//        $data['DeliveryAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);
//        $data['DeliveryState'] = $billing->getRegionCode();
//        $data['DeliveryPhone'] = $billing->getRegionCode();

        return $data;
    }

    /**
     * Remove BasketXML from request if amounts don't match.
     *
     * @param array $data
     * @return array
     */
    public function unsetBasketXMLIfAmountsDontMatch(array $data)
    {
        if(array_key_exists('BasketXML', $data) && array_key_exists('Amount', $data)) {

            $basketTotal = $this->getBasketXmlTotalAmount($data['BasketXML']);

            if(!$this->floatsEqual($data['Amount'], $basketTotal)) {
                unset($data['BasketXML']);
            }
        }

        return $data;
    }

    /**
     * @param string $basket
     * @return float
     */
    public function getBasketXmlTotalAmount($basket)
    {
        //amount = Sum of totalGrossAmount + deliveryGrossAmount - Sum of fixed (discounts)
        $xml    = null;
        $amount = 0;

        try {
            $xml = new \SimpleXMLElement($basket);
        }catch(\Exception $ex){
            return $amount;
        }

        $amount += $this->getBasketXmlItemsTotalAmount($xml->children()->item);

        $amount += (float)$xml->children()->deliveryGrossAmount;

        if(isset($xml->children()->discounts)) {
            $amount -= $this->getBasketXmlDiscountTotalAmount($xml->children()->discounts->children());
        }

        return $amount;
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

        $data = [];
        if ($isRestRequest) {
            $data["amount"]   = $amount * 100;
            $data["currency"] = $currencyCode;
        } else {
            $data["Amount"]   = $this->formatPrice($amount);
            $data["Currency"] = $currencyCode;
        }

        return $data;
    }

    public function populateBasketInformation($quote, $force_xml = false)
    {
        $data = [];

        $basketFormat = $this->_config->getBasketFormat();

        if ($basketFormat == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML || $force_xml == true) {
            $_basket = $this->_getBasketXml($quote);
            if ($this->_validateBasketXml($_basket)) {
                $data['BasketXML'] = $_basket;
            }
        } elseif ($basketFormat == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_SAGE50) {
            $data['Basket'] = $this->_getBasketSage50($quote);
        }

        return $data;
    }

    /**
     * @param $quote \Magento\Quote\Model\Quote
     * @return string
     */
    protected function _getBasketSage50($quote)
    {

        $BASKET_SEP = ':';
        $BASKET_SEP_ESCAPE = '-';

        $basketArray = [];
//        $useBaseMoney = false; //true

        $itemsCollection = $quote->getItemsCollection();

//        $trnCurrency = (string)$this->getConfigData('trncurrency', $quote->getStoreId());
//        if ($trnCurrency == 'store' or $trnCurrency == 'switcher') {
//            $useBaseMoney = false;
//        }

        foreach ($itemsCollection as $item) {
//                //Avoid duplicates SKUs on basket
//                if ($this->_isSkuDuplicatedInSageBasket($basketArray,$this->_cleanSage50BasketString($item->getSku())) == true) {
//                    continue;
//                }

            //Avoid configurables
            if ($item->getParentItem()) {
                continue;
            }

            $itemQty = $item->getQty();

//               if($useBaseMoney){
//                    $itemDiscount = $item->getBaseDiscountAmount() / $itemQty;
//                    $taxAmount = $item->getBaseTaxAmount() / $itemQty;
//                    $itemValue = $item->getBasePriceInclTax() - $taxAmount - $itemDiscount;
//
//                }else{
            $itemDiscount = $item->getDiscountAmount() / $itemQty;
            $taxAmount = $item->getTaxAmount() / $itemQty;
            $itemValue = $item->getPriceInclTax() - $taxAmount - $itemDiscount;
//                }

            $itemTotal = $itemValue + $taxAmount;

            //Options
//                $options = $item->_getProductOptions();
//
            $_options = '';
//                if (count($options) > 0) {
//                    foreach ($options as $opt) {
//                        $this->_logger->addDebug($opt->toString());
//                        $_options .= $opt['label'] . '-' . $opt['value'] . '.';
//                    }
//                    $_options = '_' . substr($_options, 0, -1) . '_';
//                }

            $newItem = [
                "item" => "",
                "qty" => 0,
                "item_value" => 0,
                "item_tax" => 0,
                "item_total" => 0,
                "line_total" => 0
            ];

            //[SKU] Name
            $newItem["item"] = str_replace($BASKET_SEP, $BASKET_SEP_ESCAPE, '[' . $this->_cleanSage50BasketString($item->getSku()) . '] ' . $this->_cleanSage50BasketString($item->getName()) . $this->_cleanSage50BasketString($_options));

            //Quantity
            $newItem["qty"] = $itemQty;

            //Item value
            $newItem["item_value"] = number_format($itemValue, 2);

            //Item tax
            $newItem["item_tax"] = number_format($taxAmount, 3);

            //Item total
            $newItem["item_total"] = number_format($itemTotal, 2);

            //Line total
            $newItem["line_total"] = number_format($itemTotal * $itemQty, 2);

            //add item to array
            $basketArray[] = $newItem;
        }

        $shippingAddress = $quote->getShippingAddress();
        $shippingDescription = $shippingAddress->getShippingDescription();
        $deliveryName = $shippingDescription ? $shippingDescription : 'Delivery';

//        if($useBaseMoney) {
//            $deliveryValue  = $shippingAddress->getBaseShippingAmount();
//            $deliveryTax    = $shippingAddress->getBaseShippingTaxAmount();
//        }
//        else {
            $deliveryValue  = $shippingAddress->getShippingAmount();
            $deliveryTax    = $shippingAddress->getShippingTaxAmount();
//        }
        $deliveryAmount = $deliveryValue + $deliveryTax;

        //delivery item
        $deliveryItem = [
            "item"=>str_replace($BASKET_SEP, $BASKET_SEP_ESCAPE, $this->_cleanSage50BasketString($deliveryName)),
            "qty"=>1,
            "item_value"=>$deliveryValue,
            "item_tax"=>$deliveryTax,
            "item_total"=>$deliveryAmount,
            "line_total"=>$deliveryAmount];

        $basketArray[] = $deliveryItem;

        //create basket string
        $basketString = '';
        foreach ($basketArray as $item) {
            $basketString .= $BASKET_SEP . implode($BASKET_SEP, $item);
        }

        //add total rows
        $basketString = count($basketArray) . $basketString;

        return $basketString;
    }

    /**
     * @param $quote \Magento\Quote\Model\Quote
     * @return string
     */
    protected function _getBasketXml($quote)
    {

        $basket = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><basket />');

        $shippingAdd = $quote->getShippingAddress();
        $billingAdd  = $quote->getBillingAddress();

        $itemsCollection = $quote->getItemsCollection();

        foreach ($itemsCollection as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $node = $basket->addChild('item', '');

            $itemDesc = trim(substr($item->getName(), 0, 100));
            $validDescription = preg_match_all("/.*/", $itemDesc, $matchesDescription);
            if ($validDescription === 1) {
                //<description>
                $node->addChild('description', $this->_convertStringToSafeXMLChar($itemDesc));
            } else {
                //<description>
                $node->addChild('description', $this->_convertStringToSafeXMLChar(substr(implode("", $matchesDescription[0]), 0, 100)));
            }

            $this->basketXmlProductSku($item, $node);

            //<productCode>
            $node->addChild('productCode', $item->getProductId());

            $itemQty = $item->getQty();

            $unitTaxAmount    = $this->formatPrice($item->getTaxAmount() / $itemQty);
            $unitNetAmount    = $this->formatPrice($item->getPrice());
            $unitGrossAmount  = $this->formatPrice($unitNetAmount + $unitTaxAmount);
            $totalGrossAmount = $this->formatPrice($unitGrossAmount * $itemQty);

            //<quantity>
            $node->addChild('quantity', $itemQty);
            //<unitNetAmount>
            $node->addChild('unitNetAmount', $unitNetAmount);
            //<unitTaxAmount>
            $node->addChild('unitTaxAmount', $unitTaxAmount);
            //<unitGrossAmount>
            $node->addChild('unitGrossAmount', $unitGrossAmount);
            //<totalGrossAmount>
            $node->addChild('totalGrossAmount', $totalGrossAmount);

            //<recipientFName>
            $this->basketXmlRecipientFName($shippingAdd, $node);

            //<recipientLName>
            $this->basketXmlRecipientLName($shippingAdd, $node);

            //<recipientMName>
            $this->basketXmlMiddleName($shippingAdd, $node);

            //<recipientSal>
            if ($shippingAdd->getPrefix()) {
                $recipientSal = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getPrefix()), 0, 4));
                if (!empty($recipientSal)) {
                    $node->addChild('recipientSal', $recipientSal);
                }
            }

            //<recipientEmail>
            if ($shippingAdd->getEmail()) {
                $recipientEmail = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getEmail()), 0, 45));
                if (!empty($recipientEmail)) {
                    $node->addChild('recipientEmail', $recipientEmail);
                }
            }

            //<recipientPhone>
            $recipientPhone = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getTelephone()), 0, 20));
            if (!empty($recipientPhone)) {
                $node->addChild('recipientPhone', $recipientPhone);
            }

            //<recipientAdd1>
            $address1 = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getStreetLine(1)), 0, 100));
            if (!empty($address1)) {
                $node->addChild('recipientAdd1', $address1);
            }

            //<recipientAdd2>
            if ($shippingAdd->getStreet(2)) {
                $recipientAdd2 = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getStreetLine(2)), 0, 100));
                if (!empty($recipientAdd2)) {
                    $node->addChild('recipientAdd2', $recipientAdd2);
                }
            }

            //<recipientCity>
            $recipientCity = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getCity()), 0, 40));
            if (!empty($recipientCity)) {
                $node->addChild('recipientCity', $recipientCity);
            }

            //<recipientState>
            if ($shippingAdd->getCountry() == 'US') {
                if ($quote->getIsVirtual()) {
                    $node->addChild('recipientState', $this->_convertStringToSafeXMLChar(substr(trim($billingAdd->getRegionCode()), 0, 2)));
                } else {
                    $node->addChild('recipientState', $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getRegionCode()), 0, 2)));
                }
            }

            //<recipientCountry>
            $node->addChild('recipientCountry', $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getCountry()), 0, 2)));

            //<recipientPostCode>
            $_postCode = '000';
            if ($shippingAdd->getPostcode()) {
                $_postCode = $shippingAdd->getPostcode();
            }
            $node->addChild('recipientPostCode', $this->_convertStringToSafeXMLChar($this->_sanitizePostcode(substr(trim($_postCode), 0, 9))));
        }

        //Sum up shipping totals when using SERVER with MAC
        if ($quote->getIsMultiShipping() && ($quote->getPayment()->getMethod() == 'sagepayserver')) {
            $shippingInclTax = $shippingTaxAmount = 0.00;

            $addresses = $quote->getAllAddresses();
            foreach ($addresses as $address) {
                $shippingTaxAmount += $address->getShippingTaxAmount();
                $shippingInclTax   += $address->getShippingAmount() + $shippingTaxAmount;
            }
        } else {
            $shippingTaxAmount = $shippingAdd->getShippingTaxAmount();
            $shippingInclTax   = $shippingAdd->getShippingAmount() + $shippingTaxAmount;
        }

        //<deliveryNetAmount>
        $basket->addChild('deliveryNetAmount', $this->formatPrice($shippingAdd->getShippingAmount()));

        //<deliveryTaxAmount>
        $basket->addChild('deliveryTaxAmount', $this->formatPrice($shippingTaxAmount));

        //<deliveryGrossAmount>
        $basket->addChild('deliveryGrossAmount', $this->formatPrice($shippingInclTax));

        //<shippingFaxNo>
        $this->basketXmlFaxNumber($shippingAdd, $basket);

        $xmlBasket = str_replace("\n", "", trim($basket->asXml()));

        return $xmlBasket;
    }

    /**
     * @param $value
     * @return string
     */
    protected function formatPrice($value)
    {
        return number_format($value, 2, '.', '');
    }

    protected function _cleanSage50BasketString($text)
    {
        $pattern = '|[^a-zA-Z0-9\-\._]+|';
        $text = preg_replace($pattern, '', $text);
        return $text;
    }

    protected function _convertStringToSafeXMLChar($string)
    {

        $safe_regex = '/([a-zA-Z\s\d\+\'\"\/\\\&\:\,\.\-\{\}\@])/';
        $safe_string = "";

        for ($i = 0; $i < strlen($string); $i++) {
            if (preg_match($safe_regex, substr($string, $i, 1)) != false) {
                $safe_string .= substr($string, $i, 1);
            } else {
                $safe_string .= '-';
            }
        }

        return $safe_string;
    }

    protected function _sanitizePostcode($text)
    {
        return preg_replace("/[^a-zA-Z0-9-\s]/", "", $text);
    }

    /**
     * Check if basket is OKay to be sent to Sage Pay.
     *
     * @param string $basket
     * @return boolean
     */
    protected function _validateBasketXml($basket)
    {
        //Validate max length
        $validLength  = $this->validateBasketXmlLength($basket);

        $validAmounts = $this->validateBasketXmlAmounts($basket);

        return $validLength && $validAmounts;
    }

    public function getOrderDescription($isMOTO = false)
    {
        return $isMOTO ? __("Online MOTO transaction.") : __("Online transaction.");
    }

    public function getReferrerId()
    {
        return "01bf51f9-0dcd-49dd-a07a-3b1f918c77d7";
    }

    /**
     * @param $basket
     * @return bool
     */
    public function validateBasketXmlAmounts($basket)
    {
        $valid = true;

        /**
         * unitGrossAmount = unitNetAmount + unitTaxAmount
         * totalGrossAmount = unitGrossAmount * quantity
         */

        $xml = null;

        try {
            $xml = new \SimpleXMLElement($basket);
        }catch(\Exception $ex){
            $valid = false;
        }

        $items = $xml->children()->item;

        $totalItems = count($items);

        $i = 0;
        while ($valid && $i < $totalItems) {
            $unitGrossAmount  = (float)$items[$i]->unitNetAmount + (float)$items[$i]->unitTaxAmount;
            $validUnit        = $this->floatsEqual((float)$items[$i]->unitGrossAmount, $unitGrossAmount);

            $totalGrossAmount = (float)$items[$i]->unitGrossAmount * (float)$items[$i]->quantity;
            $validTotal       = $this->floatsEqual((float)$items[$i]->totalGrossAmount, $totalGrossAmount);

            $valid = $validTotal && $validUnit;

            $i++;
        }

        return $valid;
    }

    public function floatsEqual($f1, $f2)
    {
        return abs(($f1-$f2)/$f2) < 0.00001;
    }

    /**
     * @param $basket
     * @return bool
     */
    public function validateBasketXmlLength($basket)
    {
        $valid = true;
        if (strlen($basket) > 20000) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * @param $shippingAdd
     * @param $node
     */
    protected function basketXmlRecipientFName($shippingAdd, $node)
    {
        $recipientFName = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getFirstname()), 0, 20));
        if (!empty($recipientFName)) {
            $node->addChild('recipientFName', $recipientFName);
        }
    }

    /**
     * @param $shippingAdd
     * @param $node
     */
    protected function basketXmlRecipientLName($shippingAdd, $node)
    {
        $recipientLName = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getLastname()), 0, 20));
        if (!empty($recipientLName)) {
            $node->addChild('recipientLName', $recipientLName);
        }
    }

    /**
     * @param $shippingAdd
     * @param $node
     */
    protected function basketXmlMiddleName($shippingAdd, $node)
    {
        if ($shippingAdd->getMiddlename()) {
            $recipientMName = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getMiddlename()), 0, 1));
            if (!empty($recipientMName)) {
                $node->addChild('recipientMName', $recipientMName);
            }
        }
    }

    /**
     * @param $item
     * @param $node
     */
    protected function basketXmlProductSku($item, $node)
    {
        $validSku = preg_match_all("/[\p{L}0-9\s\-]+/", $item->getSku(), $matchesSku);
        if ($validSku === 1) {
            //<productSku>
            $node->addChild('productSku', substr($item->getSku(), 0, 12));
        }
    }

    /**
     * @param $shippingAdd
     * @param $basket
     */
    protected function basketXmlFaxNumber($shippingAdd, $basket)
    {
        $validFax = preg_match_all("/[a-zA-Z0-9\-\s\(\)\+]+/", trim($shippingAdd->getFax()), $matchesFax);
        if ($validFax === 1) {
            $basket->addChild('shippingFaxNo', substr(trim($shippingAdd->getFax()), 0, 20));
        }
    }

    private function getBasketXmlDiscountTotalAmount($discounts)
    {
        $amount = 0;

        $totalDiscounts = count($discounts);

        $i = 0;
        while ($i < $totalDiscounts) {
            $amount += (float)$discounts[$i]->fixed;
            $i++;
        }

        return $amount;
    }

    /**
     * @param $items
     * @return float
     */
    private function getBasketXmlItemsTotalAmount($items)
    {
        $amount = 0;
        $totalItems = count($items);

        $i = 0;
        while ($i < $totalItems) {
            $amount += (float)$items[$i]->totalGrossAmount;
            $i++;
        }
        return $amount;
    }
}
