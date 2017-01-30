<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

abstract class RequestManagement implements \Ebizmarts\SagePaySuite\Api\PiOrderPlaceInterface
{
    /** @var \Ebizmarts\SagePaySuite\Model\Api\PIRest */
    private $piRestApi;

    /** @var \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType */
    private $ccConverter;

    /** @var \Ebizmarts\SagePaySuite\Model\PiRequest */
    private $piRequest;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface */
    private $requestData;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface */
    private $payResult;

    private $suiteHelper;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface $result */
    private $result;

    /** @var \Ebizmarts\SagePaySuite\Helper\Checkout */
    private $checkoutHelper;

    /** @var string */
    private $vendorTxCode;

    public function __construct(
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $piRestApi,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert,
        \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface $result
    ) {
        $this->piRestApi      = $piRestApi;
        $this->ccConverter    = $ccConvert;
        $this->piRequest      = $piRequest;
        $this->suiteHelper    = $suiteHelper;
        $this->result         = $result;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface
     */
    abstract public function placeOrder();

    /**
     * @return boolean
     */
    abstract public function getIsMotoTransaction();

    public function getRequest()
    {
        $this->getQuote()->collectTotals();
        $this->getQuote()->reserveOrderId();

        return $this->piRequest
            ->setCart($this->getQuote())
            ->setMerchantSessionKey($this->getRequestData()->getMerchantSessionKey())
            ->setCardIdentifier($this->getRequestData()->getCardIdentifier())
            ->setVendorTxCode($this->getVendorTxCode())
            ->setIsMoto($this->getIsMotoTransaction())
            ->getRequestData();
    }

    public function getPayResult()
    {
        return $this->payResult;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface
     */
    public function pay()
    {
        //@TODO: Improve here to support Deferred, Authenticate.
        $this->payResult = $this->piRestApi->capture($this->getRequest());

        return $this->payResult;
    }

    /**
     * @throws \Magento\Framework\Validator\Exception
     */
    public function processPayment()
    {
        if ($this->getPayResult()->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS ||
            $this->getPayResult()->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::AUTH3D_REQUIRED_STATUS
        ) {
            //set payment info for save order
            $payment = $this->getQuote()->getPayment();

            $this->saveAdditionalPaymentInformation($payment);

            $this->saveCreditCardInformationInPayment($payment);

        } else {
            throw new \Magento\Framework\Validator\Exception(
                __('Invalid Sage Pay response, please use another payment method.')
            );
        }
    }

    private function saveAdditionalPaymentInformation()
    {
        $this->getQuote()->getPayment()->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->getQuote()->getPayment()->setTransactionId($this->getPayResult()->getTransactionId());
        $this->getQuote()->getPayment()->setAdditionalInformation('statusCode', $this->getPayResult()->getStatusCode());
        $this->getQuote()->getPayment()->setAdditionalInformation('statusDetail', $this->getPayResult()->getStatusDetail());
        $this->getQuote()->getPayment()->setAdditionalInformation('vendorTxCode', $this->getVendorTxCode());
        if ($this->getPayResult()->getThreeDSecure() !== null) {
            $this->getQuote()->getPayment()->setAdditionalInformation('threeDStatus', $this->getPayResult()->getThreeDSecure()->getStatus());
        }
        $this->getQuote()->getPayment()->setAdditionalInformation('moto', $this->getIsMotoTransaction());
        $this->getQuote()->getPayment()->setAdditionalInformation('vendorname', $this->getRequestData()->getVendorName());
        $this->getQuote()->getPayment()->setAdditionalInformation('mode', $this->getRequestData()->getMode());
        $this->getQuote()->getPayment()->setAdditionalInformation('paymentAction', $this->getRequestData()->getPaymentAction());
    }

    private function saveCreditCardInformationInPayment()
    {
        //DropIn
        if ($this->getPayResult()->getPaymentMethod() !== null) {
            $card = $this->getPayResult()->getPaymentMethod()->getCard();
            if ($card !== null) {
                $this->getQuote()->getPayment()->setCcLast4($card->getLastFourDigits());
                $this->getQuote()->getPayment()->setCcExpMonth($card->getExpiryMonth());
                $this->getQuote()->getPayment()->setCcExpYear($card->getExpiryYear());
                $this->getQuote()->getPayment()->setCcType($this->ccConverter->convert($card->getCardType()));
            }
        } else {
            //Custom cc form
            $this->getQuote()->getPayment()->setCcLast4($this->getRequestData()->getCcLastFour());
            $this->getQuote()->getPayment()->setCcExpMonth($this->getRequestData()->getCcExpMonth());
            $this->getQuote()->getPayment()->setCcExpYear($this->getRequestData()->getCcExpYear());
            $this->getQuote()->getPayment()->setCcType($this->ccConverter->convert($this->getRequestData()->getCcType()));
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     */
    public function setQuote(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @return string
     */
    public function getVendorTxCode()
    {
        if ($this->vendorTxCode === null) {
            $this->vendorTxCode = $this->suiteHelper->generateVendorTxCode($this->getQuote()->getReservedOrderId());
        }

        return $this->vendorTxCode;
    }

    /**
     * @param \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface $data
     */
    public function setRequestData(\Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface $data)
    {
        $this->requestData = $data;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    public function getCheckoutHelper()
    {
        return $this->checkoutHelper;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface
     */
    public function getRequestData()
    {
        return $this->requestData;
    }
}