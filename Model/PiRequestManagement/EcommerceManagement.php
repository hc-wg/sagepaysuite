<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/27/17
 * Time: 12:18 PM
 */

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class EcommerceManagement extends RequestManagement
{
    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    private $sagePaySuiteLogger;

    public function __construct(
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $piRestApi,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert,
        \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface $result,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $sagePaySuiteLogger
    ) {
        parent::__construct(
            $checkoutHelper,
            $piRestApi,
            $ccConvert,
            $piRequest,
            $suiteHelper,
            $result
        );
        $this->checkoutSession    = $checkoutSession;
        $this->sagePaySuiteLogger = $sagePaySuiteLogger;
    }

    /**
     * @inheritDoc
     */
    public function getIsMotoTransaction()
    {
        return false;
    }

    public function placeOrder()
    {
        try {
            $this->tryToChargeCustomerAndCreateOrder();
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->tryToVoidTransactionLogErrorAndUpdateResult($apiException);
        } catch (\Exception $e) {
            $this->tryToVoidTransactionLogErrorAndUpdateResult($e);
        }

        return $this->getResult();
    }

    private function tryToChargeCustomerAndCreateOrder()
    {
        $this->pay();

        $this->processPayment();

        //save order with pending payment
        $order = $this->getCheckoutHelper()->placeOrder();

        if ($order !== null) {
            //set pre-saved order flag in checkout session
            $this->checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", $order->getId());

            $payment = $order->getPayment();
            $payment->setTransactionId($this->getPayResult()->getTransactionId());
            $payment->setLastTransId($this->getPayResult()->getTransactionId());
            $payment->save();

            $this->createInvoiceForSuccessPayment($payment, $order);
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
        }

        $this->getResult()->setSuccess(true);
        $this->getResult()->setTransactionId($this->getPayResult()->getTransactionId());
        $this->getResult()->setStatus($this->getPayResult()->getStatus());

        //additional details required for callback URL
        $this->getResult()->setOrderId($order->getId());
        $this->getResult()->setQuoteId($this->getQuote()->getId());

        if ($this->getPayResult()->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::AUTH3D_REQUIRED_STATUS) {
            $this->getResult()->setParEq($this->getPayResult()->getParEq());
            $this->getResult()->setAcsUrl($this->getPayResult()->getAcsUrl());
        }
    }

    /**
     * @param $payment
     * @param $order
     */
    private function createInvoiceForSuccessPayment($payment, $order)
    {
        //invoice
        if ($this->getPayResult()->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
            $payment->getMethodInstance()->markAsInitialized();
            $order->place()->save();

            //send email
            $this->getCheckoutHelper()->sendOrderEmail($order);

            //prepare session to success page
            $this->checkoutSession->clearHelperData();
            //set last successful quote
            $this->checkoutSession->setLastQuoteId($this->getQuote()->getId());
            $this->checkoutSession->setLastSuccessQuoteId($this->getQuote()->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
        }
    }

    /**
     * @param $exceptionObject
     */
    private function tryToVoidTransactionLogErrorAndUpdateResult($exceptionObject)
    {
        $this->sagePaySuiteLogger->logException($exceptionObject);
        $this->getResult()->setSuccess(false);
        $this->getResult()->setErrorMessage(__('Something went wrong: ' . $exceptionObject->getMessage()));

        if ($this->getPayResult() !== null) {
            $this->getPiRestApi()->void($this->getPayResult()->getTransactionId());
        }
    }
}
