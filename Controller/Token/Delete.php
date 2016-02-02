<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Token;


use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;


class Delete extends \Magento\Framework\App\Action\Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    protected $_tokenModel;

    protected $_tokenId;
    protected $_isCustomerArea;

    /**
     * @var Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $_postApi;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        \Magento\Customer\Model\Session $customerSession,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        \Ebizmarts\SagePaySuite\Model\Config $config
    )
    {
        parent::__construct($context);
        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $logger;
        $this->_tokenModel = $tokenModel;
        $this->_customerSession = $customerSession;
        $this->_postApi = $postApi;
        $this->_config = $config;

        $this->_isCustomerArea = false;

        $postData = $this->getRequest();
        $postData = preg_split('/^\r?$/m', $postData, 2);
        $postData = json_decode(trim($postData[1]));
        if (!is_null($postData) && isset($postData->token_id)) {
            $this->_tokenId = $postData->token_id;
        }
    }

    public function execute()
    {
        try {
            if (empty($this->_tokenId)) {
                //try get parameter, might be comming from the customer area
                if(!empty($this->getRequest()->getParam("token_id"))){
                    $this->_tokenId = $this->getRequest()->getParam("token_id");
                    $this->_isCustomerArea = true;
                }else {
                    throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Invalid token id.'));
                }
            }

            $token = $this->_tokenModel->loadToken($this->_tokenId);

            //validate ownership
            if ($token->isOwnedByCustomer($this->_customerSession->getCustomerId())) {

                //delete token from sagepay
                $this->_deleteFromSagePay($token);

                //delete from DB
                $token->deleteToken();

            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Token is not owned by you'));
            }

            //prepare response
            $responseContent = [
                'success' => true,
                'response' => true
            ];

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            $this->_logger->critical($apiException);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $apiException->getUserMessage()),
            ];

        } catch (\Exception $e) {

            $this->_logger->critical($e);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
        }

        if ($this->_isCustomerArea == true) {
            if ($responseContent["success"] == true) {
                $this->messageManager->addSuccess(__('Token deleted successfully.'));
                $this->_redirect('sagepaysuite/customer/tokens');
            } else {
                $this->messageManager->addError(__('Something went wrong: ' . $responseContent["error_message"]));
                $this->_redirect('sagepaysuite/customer/tokens');
            }
        } else {
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($responseContent);
            return $resultJson;
        }
    }

    protected function _deleteFromSagePay($token)
    {
        try {

            //generate delete POST request
            $data = array();
            $data["VPSProtocol"] = $this->_config->getVPSProtocol();
            $data["TxType"] = "REMOVETOKEN";
            $data["Vendor"] = $token->getVendorname();
            $data["Token"] = $token->getToken();

            //send POST to Sage Pay
            $this->_postApi->sendPost($data,
                $this->_getServiceURL(),
                array("OK")
            );

        }catch (\Exception $e) {

            $this->_logger->critical($e);
            //we do not show any error message to frontend
        }
    }

    private function _getServiceURL(){
        if($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_TOKEN_POST_REMOVE_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_TOKEN_POST_REMOVE_TEST;
        }
    }
}
