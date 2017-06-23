<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Api\FormManagementInterface;

class FormRequestManagement implements FormManagementInterface
{

    /** @var \Ebizmarts\SagePaySuite\Api\Data\FormResultInterface  */
    private $result;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    private $_requestHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /** @var \Ebizmarts\SagePaySuite\Helper\Checkout */
    private $checkoutHelper;

    private $transactionVendorTxCode;

    private $formCrypt;

    public function __construct(
        Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Api\Data\FormResultInterface $result,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Framework\UrlInterface $coreUrl,
        \Ebizmarts\SagePaySuite\Model\FormCrypt $formCrypt
    ) {
    
        $this->result             = $result;
        $this->quoteRepository    = $quoteRepository;
        $this->_config            = $config;
        $this->_suiteHelper       = $suiteHelper;
        $this->_checkoutSession   = $checkoutSession;
        $this->_customerSession   = $customerSession;
        $this->_suiteLogger       = $suiteLogger;
        $this->_requestHelper     = $requestHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->url                = $coreUrl;
        $this->formCrypt          = $formCrypt;
        $this->checkoutHelper     = $checkoutHelper;

        $this->_config->setMethodCode(Config::METHOD_FORM);
    }

    /**
     * @param $cartId
     * @return \Ebizmarts\SagePaySuite\Api\Data\ResultInterface|ResultInterface
     */
    public function getEncryptedRequest($cartId)
    {
        try {
            $this->_quote = $this->getQuoteById($cartId);
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();

            $vendorname = $this->_config->getVendorname();
            $this->transactionVendorTxCode = $this->_suiteHelper->generateVendorTxCode(
                $this->_quote->getReservedOrderId()
            );

            //set payment info for save order
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);

            //save order with pending payment
            /** @var \Magento\Sales\Api\Data\OrderInterface $order */
            $order = $this->checkoutHelper->placeOrder();
            if ($order->getEntityId()) {
                //set pre-saved order flag in checkout session
                $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", $order->getId());

                //set payment data
                $payment = $order->getPayment();
                $payment->setAdditionalInformation('vendorTxCode', $this->transactionVendorTxCode);
                $payment->setAdditionalInformation('vendorname', $vendorname);
                $payment->setAdditionalInformation('mode', $this->_config->getMode());
                $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());
                $payment->save();

                $this->result->setSuccess(true);
                $this->result->setRedirectUrl($this->getFormRedirectUrl());
                $this->result->setVpsProtocol($this->_config->getVPSProtocol());
                $this->result->setTxType($this->_config->getSagepayPaymentAction());
                $this->result->setVendor($vendorname);
                $this->result->setCrypt($this->generateFormCrypt());
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
            }
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);

            $this->result->setSuccess(false);
            $this->result->setErrorMessage(__('Something went wrong: ' . $e->getMessage()));
        }

        return $this->result;
    }

    /**
     * @return string
     */
    private function getFormRedirectUrl()
    {
        $url = Config::URL_FORM_REDIRECT_LIVE;

        if ($this->_config->getMode()== Config::MODE_TEST) {
            $url = Config::URL_FORM_REDIRECT_TEST;
        }

        return $url;
    }

    private function generateFormCrypt()
    {

        $encryptedPassword = $this->_config->getFormEncryptedPassword();

        if (empty($encryptedPassword)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid FORM encrypted password.'));
        }

        $data = [];
        $data['VendorTxCode'] = $this->transactionVendorTxCode;
        $data['Description']  = $this->_requestHelper->getOrderDescription();

        //referrer id
        $data["ReferrerID"] = $this->_requestHelper->getReferrerId();

        if ($this->_config->getBasketFormat() != Config::BASKETFORMAT_DISABLED) {
            $data = array_merge($data, $this->_requestHelper->populateBasketInformation($this->_quote));
        }

        $data['SuccessURL'] = $this->url->getUrl('sagepaysuite/form/success', [
            '_secure' => true,
            '_store'  => $this->_quote->getStoreId(),
            'quoteid' => $this->_quote->getId()
        ]);
        $data['FailureURL'] = $this->url->getUrl('sagepaysuite/form/failure', [
            '_secure' => true,
            '_store'  => $this->_quote->getStoreId(),
            'quoteid' => $this->_quote->getId()
        ]);

        //email details
        $data['VendorEMail']  = $this->_config->getFormVendorEmail();
        $data['SendEMail']    = $this->_config->getFormSendEmail();
        $data['EmailMessage'] = substr($this->_config->getFormEmailMessage(), 0, 7500);

        //populate payment amount information
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmount($this->_quote));

        $data = $this->_requestHelper->unsetBasketXMLIfAmountsDontMatch($data);

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        $data["CardHolder"]    = $data['BillingFirstnames'] . ' ' . $data['BillingSurname'];

        //3D rules
        $data["Apply3DSecure"] = $this->_config->get3Dsecure();

        //Avs/Cvc rules
        $data["ApplyAVSCV2"]   = $this->_config->getAvsCvc();

        //gif aid
        $data["AllowGiftAid"]  = (int)$this->_config->isGiftAidEnabled();

        //log request
        $this->_suiteLogger->sageLog(Logger\Logger::LOG_REQUEST, $data, [__METHOD__, __LINE__]);

        $preCryptString = '';
        foreach ($data as $field => $value) {
            if ($value != '') {
                $preCryptString .= ($preCryptString == '') ? "$field=$value" : "&$field=$value";
            }
        }

        return $this->encryptRequest($encryptedPassword, $preCryptString);
    }

    /**
     * @param $encryptedPassword
     * @param $preCryptString
     * @return string
     */
    private function encryptRequest($encryptedPassword, $preCryptString)
    {
        $this->formCrypt->initInitializationVectorAndKey($encryptedPassword);

        $crypt = $this->formCrypt->encrypt($preCryptString);

        return $crypt;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        return $this->quoteRepository->get($cartId);
    }

    public function getQuoteRepository()
    {
        return $this->quoteRepository;
    }

    public function getQuoteIdMaskFactory()
    {
        return $this->quoteIdMaskFactory;
    }
}
