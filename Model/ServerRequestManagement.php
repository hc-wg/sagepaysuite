<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite;

class ServerRequestManagement implements \Ebizmarts\SagePaySuite\Api\ServerManagementInterface
{

    /** @var ResultInterface  */
    protected $result;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_coreUrl;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var string
     */
    protected $_assignedVendorTxCode;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $_checkoutHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $_postApi;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    protected $_requestHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    protected $_tokenModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Ebizmarts\SagePaySuite\Api\Data\ResultInterface $result,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\UrlInterface $coreUrl,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
    
        $this->result             = $result;
        $this->quoteRepository    = $quoteRepository;
        $this->_config            = $config;
        $this->_suiteHelper       = $suiteHelper;
        $this->_postApi           = $postApi;
        $this->_checkoutSession   = $checkoutSession;
        $this->_customerSession   = $customerSession;
        $this->_quote             = $this->_checkoutSession->getQuote();
        $this->_suiteLogger       = $suiteLogger;
        $this->_checkoutHelper    = $checkoutHelper;
        $this->_requestHelper     = $requestHelper;
        $this->_tokenModel        = $tokenModel;
        $this->_coreUrl           = $coreUrl;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;

        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
    }

    /**
     * Set payment information and place order for a specified cart.
     *
     * @param mixed $cartId
     * @param bool $save_token
     * @param string $token
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \Ebizmarts\SagePaySuite\Api\Data\ResultInterface
     */
    public function savePaymentInformationAndPlaceOrder($cartId, $save_token, $token)
    {

        try {
            //prepare quote
            $quote = $this->getQuoteById($cartId);
            $quote->collectTotals();
            $quote->reserveOrderId();

            //generate POST request
            $request = $this->_generateRequest($save_token, $token);

            //send POST to Sage Pay
            $post_response = $this->_postApi->sendPost(
                $request,
                $this->_getServiceURL(),
                ["OK"]
            );

            //set payment info for save order
            $transactionId = $post_response["data"]["VPSTxId"];
            $transactionId = str_replace(["}", "{"], [""], $transactionId);
            $payment       = $quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);

//            if (!$quote->isVirtual() && $quote->getShippingAddress()) {
//                $quote->getShippingAddress()->setCollectShippingRates(true);
//            }
//
//            $data = ['method' => Config::METHOD_SERVER];
//            $data['checks'] = [
//                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_CHECKOUT,
//                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
//                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
//                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
//                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
//            ];
//            $payment = $quote->getPayment();
//            $payment->importData($data);
//            $this->quoteRepository->save($quote);

            //save order with pending payment
            $order = $this->_checkoutHelper->placeOrder($quote);

            if ($order) {
                //set pre-saved order flag in checkout session
                $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", $order->getId());

                //set payment data
                $payment = $order->getPayment();
                $payment->setTransactionId($transactionId);
                $payment->setLastTransId($transactionId);
                $payment->setAdditionalInformation('vendorTxCode', $this->_assignedVendorTxCode);
                $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
                $payment->setAdditionalInformation('mode', $this->_config->getMode());
                $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());
                $payment->setAdditionalInformation('securityKey', $post_response["data"]["SecurityKey"]);
                $payment->save();

                //prepare response
                $this->result->setSuccess(true);
                $this->result->setResponse($post_response);
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
            }
        } catch (Api\ApiException $apiException) {
            $this->_suiteLogger->logException($apiException);

            $this->result->setSuccess(false);
            $this->result->setErrorMessage(__('Something went wrong while generating the Sage Pay request: ' . $apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);

            $this->result->setSuccess(false);
            $this->result->setErrorMessage(__('Something went wrong while generating the Sage Pay request: ' . $e->getMessage()));
        }

        return $this->result;
    }

    protected function _getNotificationUrl()
    {
        $url = $this->_coreUrl->getUrl('sagepaysuite/server/notify', [
            '_secure' => true,
            '_store' => $this->_quote->getStoreId()
        ]);

        $url .= "?quoteid=" . $this->_quote->getId();

        return $url;
    }

    /**
     * @return string
     */
    protected function _getServiceURL()
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_TEST;
        }
    }

    /**
     * @param $save_token
     * @param $token
     * @return array
     */
    protected function _generateRequest($save_token, $token)
    {

        $data                    = [];
        $data["VPSProtocol"]     = $this->_config->getVPSProtocol();
        $data["TxType"]          = $this->_config->getSagepayPaymentAction();
        $data["Vendor"]          = $this->_config->getVendorname();
        $data["VendorTxCode"]    = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
        $data["Description"]     = $this->_requestHelper->getOrderDescription();
        $data["NotificationURL"] = $this->_getNotificationUrl();
        $data["ReferrerID"]      = $this->_requestHelper->getReferrerId();

        //populate payment amount information
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmount($this->_quote));

        if ($this->_config->getBasketFormat() != Config::BASKETFORMAT_DISABLED) {
            $data = array_merge($data, $this->_requestHelper->populateBasketInformation($this->_quote));
        }

        $data = $this->_requestHelper->unsetBasketXMLIfAmountsDontMatch($data);

        //address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        //token
        $customer_data = $this->_customerSession->getCustomerDataObject();
        $slots         = $this->_tokenModel->isCustomerUsingMaxTokenSlots($customer_data->getId(), $this->_config->getVendorname());
        if ($save_token && !empty($customer_data) && !$slots) {
            //save token
            $data["CreateToken"] = 1;
        } else {
            if ($token !== '%token%' && !is_null($token)) {
                //use token
                $data["StoreToken"] = 1;
                $data["Token"]      = $token;
            }
        }

        $data["Apply3DSecure"]    = $this->_config->get3Dsecure();
        $data["ApplyAVSCV2"]      = $this->_config->getAvsCvc();
        $data["AllowGiftAid"]     = (int)$this->_config->isGiftAidEnabled();
        $data["BillingAgreement"] = (int)$this->_config->getPaypalBillingAgreement();

        //server profile
        if ((bool)$this->_config->isServerLowProfileEnabled() === true) {
            $data["Profile"] = "LOW";
        }

        //not mandatory
//        CustomerXML
//        SurchargeXML
//        VendorData
//        Language
//        Website
//        FIRecipientAcctNumber
//        FIRecipientSurname
//        FIRecipientPostcode
//        FIRecipientDoB

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        return $this->quoteRepository->get($cartId);
    }
}
