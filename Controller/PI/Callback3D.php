<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

class Callback3D extends \Magento\Framework\App\Action\Action
{
    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $_config;

    /** @var \Psr\Log\LoggerInterface */
    private $_logger;

    /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement */
    private $requester;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /**
     * Callback3D constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $requester
     * @param \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory $piReqManagerFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $requester,
        \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory $piReqManagerFactory
    ) {
        parent::__construct($context);
        $this->_config             = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_logger             = $logger;

        $this->requester = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
    }

    public function execute()
    {
        try {
            /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager $data */
            $data = $this->piRequestManagerDataFactory->create();
            $data->setTransactionId($this->getRequest()->getParam("transactionId"));
            $data->setParEs($this->getRequest()->getPost('PaRes'));
            $data->setVendorName($this->_config->getVendorname());
            $data->setMode($this->_config->getMode());
            $data->setPaymentAction($this->_config->getSagepayPaymentAction());

            $this->requester->setRequestData($data);

            $response = $this->requester->placeOrder();

            if ($response->getErrorMessage() === null) {
                $this->_javascriptRedirect('checkout/onepage/success');
            }
            else {
                $this->messageManager->addError($response->getErrorMessage());
                $this->_javascriptRedirect('checkout/cart');
            }
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->_javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addError("Something went wrong: " . $e->getMessage());
            $this->_javascriptRedirect('checkout/cart');
        }
    }

    private function _javascriptRedirect($url)
    {
        //redirect to success via javascript
        $this
            ->getResponse()
            ->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl($url, ['_secure' => true])
            . '";</script>'
        );
    }
}
