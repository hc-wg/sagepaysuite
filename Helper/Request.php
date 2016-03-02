<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

class Request extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    const BASKET_SEP                           = ':';
    const BASKET_SEP_ESCAPE                    = '-';

    public function __construct(
        \Psr\Log\LoggerInterface $logger, //log injection
        \Ebizmarts\SagePaySuite\Model\Config $config
    ) {
        $this->_logger = $logger;
        $this->_config = $config;
    }

    public function populateAddressInformation($quote){

        $billing_address = $quote->getBillingAddress();
        $shipping_address = $quote->isVirtual() ? $billing_address : $quote->getShippingAddress();

        $data = array();

        $data['BillingSurname']    = substr($billing_address->getLastname(), 0, 20);
        $data['BillingFirstnames'] = substr($billing_address->getFirstname(), 0, 20);
        $data['BillingAddress1']   = substr($billing_address->getStreetLine(1), 0, 100);
        $data['BillingCity']       = substr($billing_address->getCity(), 0,  40);
        $data['BillingPostCode']   = substr($billing_address->getPostcode(), 0, 10);
        $data['BillingCountry']    = substr($billing_address->getCountryId(), 0, 2);

        //only send state if US due to Sage Pay 2 char restriction
        if ($data['BillingCountry'] == 'US') {
            $data['BillingState'] = substr($billing_address->getRegionCode(), 0, 2);
        }

        //not mandatory
//        $data['BillingAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);

        //mandatory
        $data['DeliverySurname']    = substr($shipping_address->getLastname(), 0, 20);
        $data['DeliveryFirstnames'] = substr($shipping_address->getFirstname(), 0, 20);
        $data['DeliveryAddress1']   = substr($shipping_address->getStreetLine(1), 0, 100);
        $data['DeliveryCity']       = substr($shipping_address->getCity(), 0,  40);
        $data['DeliveryPostCode']   = substr($shipping_address->getPostcode(), 0, 10);
        $data['DeliveryCountry']    = substr($shipping_address->getCountryId(), 0, 2);
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

    public function populateBasketInformation($quote, $force_xml = false) {

        $data = array();

        $basketFormat = $this->_config->getBasketFormat();
        if($basketFormat == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML || $force_xml == true) {
            $_basket = $this->getBasketXml($quote);
            if($this->validateBasketXml($_basket)) {
                $data['BasketXML'] = $_basket;
            }
        }elseif($basketFormat == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_Sage50) {
            $data['Basket'] = $this->getBasketSage50($quote);
        }

        return $data;
    }

    private function getBasketSage50($quote) {

        $basketArray = array();
        $useBaseMoney = false; //true

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

               if($useBaseMoney){
                    $itemDiscount = $item->getBaseDiscountAmount() / $itemQty;
                    $taxAmount = $item->getBaseTaxAmount() / $itemQty;
                    $itemValue = $item->getBasePriceInclTax() - $taxAmount - $itemDiscount;

                }else{
                    $itemDiscount = $item->getDiscountAmount() / $itemQty;
                    $taxAmount = $item->getTaxAmount() / $itemQty;
                    $itemValue = $item->getPriceInclTax() - $taxAmount - $itemDiscount;
                }

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

                $newItem = array(
                    "item" => "",
                    "qty" => 0,
                    "item_value" => 0,
                    "item_tax" => 0,
                    "item_total" => 0,
                    "line_total" => 0
                );

                //[SKU] Name
                $newItem["item"] = str_replace(self::BASKET_SEP, self::BASKET_SEP_ESCAPE, '[' . $this->_cleanSage50BasketString($item->getSku()) . '] ' . $this->_cleanSage50BasketString($item->getName()) . $this->_cleanSage50BasketString($_options));

                //Quantity
                $newItem["qty"] = $itemQty;

                //Item value
                $newItem["item_value"] = $itemValue;

                //Item tax
                $newItem["item_tax"] = number_format($taxAmount, 3);

                //Item total
                $newItem["item_total"] = $itemTotal;

                //Line total
                $newItem["line_total"] = $itemTotal * $itemQty;

                //add item to array
                $basketArray[] = $newItem;
            }

        $shippingAddress = $quote->getShippingAddress();
        $shippingDescription = $shippingAddress->getShippingDescription();
        $deliveryName = $shippingDescription ? $shippingDescription : 'Delivery';

        if($useBaseMoney) {
            $deliveryValue  = $shippingAddress->getBaseShippingAmount();
            $deliveryTax    = $shippingAddress->getBaseShippingTaxAmount();
            $deliveryAmount = $shippingAddress->getBaseShippingInclTax();
        }
        else {
            $deliveryValue  = $shippingAddress->getShippingAmount();
            $deliveryTax    = $shippingAddress->getShippingTaxAmount();
            $deliveryAmount = $shippingAddress->getShippingInclTax();
        }

        //delivery item
        $deliveryItem = array("item"=>str_replace(self::BASKET_SEP, self::BASKET_SEP_ESCAPE, $this->_cleanSage50BasketString($deliveryName)),
            "qty"=>1,
            "item_value"=>$deliveryValue,
            "item_tax"=>$deliveryTax,
            "item_total"=>$deliveryAmount,
            "line_total"=>$deliveryAmount);

        $basketArray[] = $deliveryItem;

        //create basket string
        $basketString = '';
        foreach ($basketArray as $item){
            $basketString .= self::BASKET_SEP . implode(self::BASKET_SEP, $item);
        }

        //add total rows
        $basketString = count($basketArray) . $basketString;

//        $this->_logger->addDebug(print_r(json_encode($basketArray), 1));
//        $this->_logger->addDebug($basketString);
//        $this->_logger->addDebug("Crush!");
//        crush;
        return $basketString;

    }

    /**
     * The basket can be passed as an xml document with extra information that
     * can be used for more accurate fraud screening through ReD.
     *
     * @param $quote
     * @return string
     */
    private function getBasketXml($quote)
    {

        $basket = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><basket />');

//        if($this->_getIsAdmin()) {
//
//            $uname = trim(Mage::getSingleton('admin/session')->getUser()->getUsername());
//
//            $validAgent = preg_match_all("/[a-zA-Z0-9\s]+/", $uname, $matchesUname);
//            if($validAgent !== 1) {
//                $uname = implode("", $matchesUname[0]);
//            }
//
//            //<agentId>
//            $basket->addChildCData('agentId', substr($uname, 0, 16));
//        }

        $discount = null;

        $shippingAdd = $quote->getShippingAddress();
        $billingAdd = $quote->getBillingAddress();

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

            $validSku = preg_match_all("/[\p{L}0-9\s\-]+/", $item->getSku(), $matchesSku);
            if ($validSku === 1) {
                //<productSku>
                $node->addChild('productSku', substr($item->getSku(), 0, 12));
            }

            //<productCode>
            $node->addChild('productCode', $item->getId());

            $itemQty = $item->getQty();

            if ($item->getDiscountAmount()) {
                $discount += $item->getDiscountAmount();
            }

            $unitTaxAmount = number_format(($item->getTaxAmount() / $itemQty), 2, '.', '');
            $unitNetAmount = number_format(($item->getPrice() + $item->getWeeeTaxAppliedAmount()), 2, '.', '');
            $unitGrossAmount = number_format($unitNetAmount + $unitTaxAmount, 2, '.', '');
            $totalGrossAmount = number_format($unitGrossAmount * $itemQty, 2, '.', '');

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
            $recipientFName = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getFirstname()), 0, 20));
            if (!empty($recipientFName)) {
                $node->addChild('recipientFName', $recipientFName);
            }

            //<recipientLName>
            $recipientLName = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getLastname()), 0, 20));
            if (!empty($recipientLName)) {
                $node->addChild('recipientLName', $recipientLName);
            }

            //<recipientMName>
            if ($shippingAdd->getMiddlename()) {
                $recipientMName = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getMiddlename()), 0, 1));
                if (!empty($recipientMName)) {
                    $node->addChild('recipientMName', $recipientMName);
                }
            }

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
            if(!empty($address1)) {
                $node->addChild('recipientAdd1', $address1);
            }

            //<recipientAdd2>
            if($shippingAdd->getStreet(2)) {
                $recipientAdd2 = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getStreetLine(2)), 0, 100));
                if(!empty($recipientAdd2)) {
                    $node->addChild('recipientAdd2', $recipientAdd2);
                }
            }

            //<recipientCity>
            $recipientCity = $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getCity()), 0, 40));
            if(!empty($recipientCity)) {
                $node->addChild('recipientCity', $recipientCity);
            }

            //<recipientState>
            if($shippingAdd->getCountry() == 'US') {
                if ($quote->getIsVirtual()) {
                    $node->addChild('recipientState', $this->_convertStringToSafeXMLChar(substr(trim($billingAdd->getRegionCode()), 0, 2)));
                }
                else {
                    $node->addChild('recipientState', $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getRegionCode()), 0, 2)));
                }
            }

            //<recipientCountry>
            $node->addChild('recipientCountry', $this->_convertStringToSafeXMLChar(substr(trim($shippingAdd->getCountry()), 0, 2)));

            //<recipientPostCode>
            $_postCode = '000';
            if($shippingAdd->getPostcode()) {
                $_postCode = $shippingAdd->getPostcode();
            }
            $node->addChild('recipientPostCode', $this->_convertStringToSafeXMLChar($this->sanitizePostcode(substr(trim($_postCode), 0, 9))));

        }

        //Sum up shipping totals when using SERVER with MAC
        if($quote->getIsMultiShipping() && ($quote->getPayment()->getMethod() == 'sagepayserver') ) {

            $shippingInclTax = $shippingTaxAmount = 0.00;

            $addresses = $quote->getAllAddresses();
            foreach($addresses as $address) {
                $shippingInclTax   += $address->getShippingInclTax();
                $shippingTaxAmount += $address->getShippingTaxAmount();
            }

        }
        else {
            $shippingInclTax   = $shippingAdd->getShippingInclTax();
            $shippingTaxAmount = $shippingAdd->getShippingTaxAmount();
        }

        //<deliveryNetAmount>
        $basket->addChild('deliveryNetAmount', number_format($shippingAdd->getShippingAmount(), 2, '.', ''));

        //<deliveryTaxAmount>
        $basket->addChild('deliveryTaxAmount', number_format($shippingTaxAmount, 2, '.', ''));

        //<deliveryGrossAmount>
        $basket->addChild('deliveryGrossAmount', number_format($shippingInclTax, 2, '.', ''));

        //<shippingFaxNo>
        $validFax = preg_match_all("/[a-zA-Z0-9\-\s\(\)\+]+/", trim($shippingAdd->getFax()), $matchesFax);
        if($validFax === 1) {
            $basket->addChild('shippingFaxNo', substr(trim($shippingAdd->getFax()), 0, 20));
        }

        //Discounts
        if(!is_null($discount) && $discount > 0.00) {
            $nodeDiscounts = $basket->addChild('discounts', '');
            $_discount = $nodeDiscounts->addChild('discount', '');
            $_discount->addChild('fixed', number_format($discount, 2, '.', ''));
        }

        $xmlBasket = str_replace("\n", "", trim($basket->asXml()));

        return $xmlBasket;

    }

    protected function _cleanSage50BasketString($text) {
        $pattern = '|[^a-zA-Z0-9\-\._]+|';
        $text = preg_replace($pattern, '', $text);
        return $text;
    }

    protected function _convertStringToSafeXMLChar($string){

        $safe_regex = '/([a-zA-Z\s\d\+\'\"\/\\\&\:\,\.\-\{\}\@])/';
        $safe_string = "";

        for($i = 0;$i<strlen($string);$i++){
            if(preg_match($safe_regex,substr($string,$i,1)) != FALSE){
                $safe_string .= substr($string,$i,1);
            }else{
                $safe_string .= '-';
            }
        }

        return $safe_string;
    }

    protected function sanitizePostcode($text) {
        return preg_replace("/[^a-zA-Z0-9-\s]/", "", $text);
    }

    /**
     * Check if basket is OKay to be sent to Sage Pay.
     *
     * @param string $basket
     * @return boolean
     */
    protected function validateBasketXml($basket) {
        $valid = true;

        //Validate max length
        if(strlen($basket) > 20000) {
            $valid = false;
        }

        return $valid;
    }

//    private function _isSkuDuplicatedInSageBasket($basketArray,$itemSku){
//        for($i = 0;$i<count($basketArray);$i++){
//            if(strpos($basketArray[$i]['item'], $itemSku) !== FALSE){
//                return true;
//                break;
//            }
//        }
//        return false;
//    }

//    /**
//     * Get product customize options
//     *
//     * @return array || false
//     */
//    protected function _getProductOptions($item) {
//        $options = array();
//
//        //This HELPER does not exist on all Magento versions
//        $helperClass = Mage::getConfig()->getHelperClassName('catalog/product_configuration');
//        if (FALSE === class_exists($helperClass, FALSE)) {
//            return $options;
//        }
//
//        $helper = Mage::helper('catalog/product_configuration');
//        if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
//            $options = $helper->getCustomOptions($item);
//        } elseif ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
//            $options = $helper->getConfigurableOptions($item);
//        }
//
//        return $options;
//    }

}