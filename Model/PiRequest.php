<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;


class PiRequest
{
    /** @var \Magento\Quote\Model\Quote */
    private $cart;

    /** @var  \Ebizmarts\SagePaySuite\Helper\Request */
    private $requestHelper;

    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $sagepayConfig;

    /** @var string The merchant session key used to generate the cardIdentifier. */
    private $merchantSessionKey;

    /** @var string The unique reference of the card you want to charge. */
    private $cardIdentifier;

    /** @var string Your unique reference for this transaction. Maximum of 40 characters. */
    private $vendorTxCode;

    /** @var bool */
    private $isMoto;

    public function __construct(
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Model\Config $sagepayConfig
    )
    {
        $this->requestHelper = $requestHelper;
        $this->sagepayConfig = $sagepayConfig;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $billingAddress  = $this->cart->getBillingAddress();
        $shippingAddress = $this->cart->getIsVirtual() ? $billingAddress : $this->cart->getShippingAddress();

        $data = [
            'transactionType' => $this->sagepayConfig->getSagepayPaymentAction(),
            'paymentMethod'   => [
                'card'        => [
                    'merchantSessionKey' => $this->getMerchantSessionKey(),
                    'cardIdentifier'     => $this->getCardIdentifier(),
                ]
            ],
            'vendorTxCode'      => $this->getVendorTxCode(),
            'description'       => $this->requestHelper->getOrderDescription($this->getIsMoto()),
            'customerFirstName' => $billingAddress->getFirstname(),
            'customerLastName'  => $billingAddress->getLastname(),
            'apply3DSecure'     => $this->sagepayConfig->get3Dsecure($this->getIsMoto()),
            'applyAvsCvcCheck'  => $this->sagepayConfig->getAvsCvc(),
            'referrerId'        => $this->requestHelper->getReferrerId(),
            'customerEmail'     => $billingAddress->getEmail(),
            'customerPhone'     => $billingAddress->getTelephone(),
        ];

        if ($this->getIsMoto()) {
            $data['entryMethod'] = 'TelephoneOrder';
        }
        else {
            $data['entryMethod'] = 'Ecommerce';
        }

        $data['billingAddress'] = [
            'address1'      => $billingAddress->getStreetLine(1),
            'city'          => $billingAddress->getCity(),
            'postalCode'    => $billingAddress->getPostCode(),
            'country'       => $billingAddress->getCountryId()
        ];
        if ($data['billingAddress']['country'] == 'US') {
            $data['billingAddress']['state'] = substr($billingAddress->getRegionCode(), 0, 2);
        }

        $data['shippingDetails'] = [
            'recipientFirstName' => $shippingAddress->getFirstname(),
            'recipientLastName'  => $shippingAddress->getLastname(),
            'shippingAddress1'   => $shippingAddress->getStreetLine(1),
            'shippingCity'       => $shippingAddress->getCity(),
            'shippingPostalCode' => $shippingAddress->getPostCode(),
            'shippingCountry'    => $shippingAddress->getCountryId()
        ];
        if ($data['shippingDetails']['shippingCountry'] == 'US') {
            $data['shippingDetails']['shippingState'] = substr($shippingAddress->getRegionCode(), 0, 2);
        }

        //populate payment amount information
        $data = array_merge($data, $this->requestHelper->populatePaymentAmount($this->cart, true));

        return $data;
    }

    /**
     * @return string
     */
    private function getMerchantSessionKey()
    {
        return $this->merchantSessionKey;
    }

    /**
     * @param string $merchantSessionKey
     * @return \Ebizmarts\SagePaySuite\Model\PiRequest
     */
    public function setMerchantSessionKey($merchantSessionKey)
    {
        $this->merchantSessionKey = $merchantSessionKey;
        return $this;
    }

    /**
     * @return string
     */
    private function getCardIdentifier()
    {
        return $this->cardIdentifier;
    }

    /**
     * @param string $cardIdentifier
     * @return \Ebizmarts\SagePaySuite\Model\PiRequest
     */
    public function setCardIdentifier($cardIdentifier)
    {
        $this->cardIdentifier = $cardIdentifier;
        return $this;
    }

    /**
     * @param string $vendorTxCode
     * @return \Ebizmarts\SagePaySuite\Model\PiRequest
     */
    public function setVendorTxCode($vendorTxCode)
    {
        $this->vendorTxCode = $vendorTxCode;
        return $this;
    }

    /**
     * @return string
     */
    private function getVendorTxCode()
    {
        return $this->vendorTxCode;
    }

    /**
     * @return bool
     */
    private function getIsMoto()
    {
        return $this->isMoto;
    }

    /**
     * @param bool $isMoto
     * @return \Ebizmarts\SagePaySuite\Model\PiRequest
     */
    public function setIsMoto($isMoto)
    {
        $this->isMoto = $isMoto;
        return $this;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return \Ebizmarts\SagePaySuite\Model\PiRequest
     */
    public function setCart(\Magento\Quote\Api\Data\CartInterface $cart)
    {
        $this->cart = $cart;
        return $this;
    }
}