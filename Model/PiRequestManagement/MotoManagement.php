<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/27/17
 * Time: 12:18 PM
 */

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;


class MotoManagement extends RequestManagement
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Magento\Framework\App\RequestInterface */
    private $httpRequest;

    /** @var \Magento\Backend\Model\UrlInterface */
    private $backendUrl;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $piRestApi,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert,
        \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface $result,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\RequestInterface $httpRequest,
        \Magento\Backend\Model\UrlInterface $backendUrl
    ) {
        parent::__construct(
            $checkoutHelper,
            $piRestApi,
            $ccConvert,
            $piRequest,
            $suiteHelper,
            $result
        );
        $this->checkoutSession = $checkoutSession;

        $this->objectManager = $objectManager;
        $this->httpRequest   = $httpRequest;
        $this->backendUrl    = $backendUrl;
    }

    /**
     * @inheritDoc
     */
    public function getIsMotoTransaction()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function placeOrder()
    {
        try {
            $this->pay();

            $this->processPayment();

            //save order with pending payment
            $order = $this
                ->getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($this->httpRequest->getPost('order'))
                ->createOrder();

            if ($order) {
                $this->_confirmPayment($order);

                //add success url to response
                $url = $this->backendUrl->getUrl('sales/order/view', ['order_id' => $order->getId()]);

                $this->getResult()->setSuccess(true);
                $this->getResult()->setResponse($url);
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order.'));
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            #$this->_logger->critical($apiException);
            $this->getResult()->setSuccess(false);
            $this->getResult()->setErrorMessage(__('Something went wrong: ' . $apiException->getUserMessage()));
        } catch (\Exception $e) {
            #$this->_logger->critical($e);
            $this->getResult()->setSuccess(false);
            $this->getResult()->setErrorMessage(__('Something went wrong: ' . $e->getMessage()));
        }

        return $this->getResult();

    }

    /**
     * Retrieve order create model
     *
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    private function getOrderCreateModel()
    {
        return $this->objectManager->get('Magento\Sales\Model\AdminOrder\Create');
    }

    private function _confirmPayment($order)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($this->getPayResult()->getTransactionId());
        $payment->setLastTransId($this->getPayResult()->getTransactionId());

        //leave transaction open in case defer or authorize
        if ($this->getRequestData()->getPaymentAction() == \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE ||
            $this->getRequestData()->getPaymentAction() == \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER) {
            $payment->setIsTransactionClosed(0);
        }

        $payment->save();

        $payment->getMethodInstance()->markAsInitialized();
        $order->place()->save();

        //send email
        $this->getCheckoutHelper()->sendOrderEmail($order);
    }
}