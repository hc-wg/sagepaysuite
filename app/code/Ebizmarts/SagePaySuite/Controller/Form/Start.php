<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;


class Start extends AbstractForm
{

    /**
     * Creates crypt and redirects to SagePay
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        try {
            $this->_initCheckout();

//            if ($this->_getQuote()->getIsMultiShipping()) {
//                $this->_getQuote()->setIsMultiShipping(false);
//                $this->_getQuote()->removeAllAddresses();
//            }
//
//            $customerData = $this->_customerSession->getCustomerDataObject();
//            $quoteCheckoutMethod = $this->_getQuote()->getCheckoutMethod();
//            if ($customerData->getId()) {
//                $this->_checkout->setCustomerWithAddressChange(
//                    $customerData,
//                    $this->_getQuote()->getBillingAddress(),
//                    $this->_getQuote()->getShippingAddress()
//                );
//            } elseif ((!$quoteCheckoutMethod || $quoteCheckoutMethod != Onepage::METHOD_REGISTER)
//                && !$this->_objectManager->get('Magento\Checkout\Helper\Data')->isAllowedGuestCheckout(
//                    $this->_getQuote(),
//                    $this->_getQuote()->getStoreId()
//                )
//            ) {
//                $this->messageManager->addNotice(
//                    __('To check out, please sign in with your email address.')
//                );
//
//                $this->_objectManager->get('Magento\Checkout\Helper\ExpressRedirect')->redirectLogin($this);
//                $this->_customerSession->setBeforeAuthUrl($this->_url->getUrl('*/*/*', ['_current' => true]));
//
//                return;
//            }



            //$orderId = $this->_cartManagement->placeOrder($quote->getId());

            $this->_checkout->start(
                $this->_url->getUrl('*/*/placeOrder'),
                $this->_url->getUrl('*/*/cancel')
            );

            //$this->getResponse()->setBody($this->generatePOSTRequestHtmlForm());
            $this->getResponse()->appendBody($this->generatePOSTRequestHtmlForm());

            return;

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t start SagePay FORM Checkout.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }

        $this->_redirect('checkout/cart');
    }

    protected function generatePOSTRequestHtmlForm(){

        $html_form = "<form action='" . $this->_checkout->getRedirectUrl() . "' ";
        $html_form .= "method='POST' id='form_" . "sagepaysuiteform" . "' name='form_" . "sagepaysuiteform" . "'>";
        $html_form .= "<input type='hidden' name='VPSProtocol' value='" . $this->_config->getVPSProtocol() . "'/>";
        $html_form .= "<input type='hidden' name='TxType' value='" . $this->_config->getSagepayPaymentAction() . "'/>";
        $html_form .= "<input type='hidden' name='Vendor' value='" . $this->_config->getVendorname() . "'/>";
        $html_form .= "<input type='hidden' name='Crypt' value='" . $this->_checkout->getFormCrypt() . "'/>";
        $html_form .= "</form>";

        $html = '<html><head><title>SagePay FORM</title></head><body>';
        $html.= '<code>Redirecting to SagePay...</code>';
        $html.= $html_form;
        $html.= '<script type="text/javascript">document.getElementById("form_' . "sagepaysuiteform" . '").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}
