<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;


class PlaceOrder extends AbstractForm
{

    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {

            $response = $this->decodeSagePayResponse($this->getRequest()->getParam(\Ebizmarts\SagePaySuite\Model\Config::VAR_Crypt));

            //mark order as paid
            //@toDo (the order is only saved upon return for now)

            $this->_initCheckout();
            $this->_checkout->returnFromSagePay();

            $this->_checkout->place();

            // prepare session to success or cancellation page
            $this->_getCheckoutSession()->clearHelperData();

            // "last successful quote"
            $quoteId = $this->_getQuote()->getId();
            $this->_getCheckoutSession()->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            // an order may be created
            $order = $this->_checkout->getOrder();
            if ($order) {
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $payment = $order->getPayment();

            //save cc data
            $payment->setCcType($response[\Ebizmarts\SagePaySuite\Model\Config::VAR_CardType]);
            $payment->setCcLast4($response[\Ebizmarts\SagePaySuite\Model\Config::VAR_Last4Digits]);
            $payment->setCcExpMonth($response[\Ebizmarts\SagePaySuite\Model\Config::VAR_ExpiryDate]);

            //save sagepay additional transaction data
            $payment->setAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_VendorTxCode,$response[\Ebizmarts\SagePaySuite\Model\Config::VAR_VendorTxCode]);
            $payment->setAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId,$response[\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId]);
            $payment->setAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_StatusDetail,$response[\Ebizmarts\SagePaySuite\Model\Config::VAR_StatusDetail]);
            $payment->setAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_AVSCV2,$response[\Ebizmarts\SagePaySuite\Model\Config::VAR_AVSCV2]);
            $payment->setAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_3DSecureStatus,$response[\Ebizmarts\SagePaySuite\Model\Config::VAR_3DSecureStatus]);
            $payment->setAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_BankAuthCode,$response[\Ebizmarts\SagePaySuite\Model\Config::VAR_BankAuthCode]);

            $payment->save();

            $this->_eventManager->dispatch(
                'sagepaysuiteform_place_order_success',
                [
                    'order' => $order,
                    'quote' => $this->_getQuote()
                ]
            );

            $this->_redirect('checkout/onepage/success');

            return;

        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t place the order. Please try again.'));
            $this->_logger->critical($e);
            //$this->_redirect('*/*/review');
            $this->_redirectToCartAndShowError('We can\'t place the order. Please try again.');
        }
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addError($errorMessage);
        $this->_redirect('checkout/cart');
    }

    protected function decodeSagePayResponse($crypt){
        if (empty($crypt)) {
            $this->_redirectToCartAndShowError('Invalid response from SagePay, please contact our support team to rectify payment.');
        }else{
            $strDecoded = $this->getFormModel()->decrypt($crypt);

            $responseRaw = explode('&',$strDecoded);
            $response = array();

            for($i = 0;$i < count($responseRaw);$i++){
                $strField = explode('=',$responseRaw[$i]);
                $response[$strField[0]] = $strField[1];
            }

            if(!array_key_exists(\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId,$response)){
                $this->_redirectToCartAndShowError('Invalid response from SagePay, please contact our support team to rectify payment.');
            }else{
                return $response;
            }
        }
    }
}
