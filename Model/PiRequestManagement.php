<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Api\Data\PiRequest as PiDataRequest;
use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\EcommerceManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class PiRequestManagement implements \Ebizmarts\SagePaySuite\Api\PiManagementInterface
{
    /** @var Config */
    private $config;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /** @var EcommerceManagement */
    private $requester;

    /** @var QuoteIdMaskFactory */
    private $quoteIdMaskFactory;

    /** @var Logger */
    private $suiteLogger;

    public function __construct(
        Config $config,
        CartRepositoryInterface $quoteRepository,
        PiRequestManagerFactory $piReqManagerFactory,
        EcommerceManagement $requester,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Logger $suiteLogger
    ) {
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);

        $this->requester                   = $requester;
        $this->quoteRepository             = $quoteRepository;
        $this->quoteIdMaskFactory          = $quoteIdMaskFactory;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->suiteLogger                 = $suiteLogger;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder($cartId, PiDataRequest $requestData)
    {
        /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager $data */
        $data = $this->piRequestManagerDataFactory->create();
        $data->setMode($this->config->getMode());
        $data->setVendorName($this->config->getVendorname());
        $data->setPaymentAction($this->config->getSagepayPaymentAction());
        $data->setMerchantSessionKey($requestData->getMerchantSessionKey());
        $data->setCardIdentifier($requestData->getCardIdentifier());
        $data->setCcExpMonth($requestData->getCcExpMonth());
        $data->setCcExpYear($requestData->getCcExpYear());
        $data->setCcLastFour($requestData->getCcLastFour());
        $data->setCcType($requestData->getCcType());

        $data->setJavascriptEnabled($requestData->getJavascriptEnabled());
        $data->setLanguage($requestData->getLanguage());
        $data->setUserAgent($requestData->getUserAgent());
        $data->setAcceptHeaders($requestData->getAcceptHeaders());
        $data->setJavaEnabled($requestData->getJavaEnabled());
        $data->setColorDepth($requestData->getColorDepth());
        $data->setScreenHeight($requestData->getScreenHeight());
        $data->setScreenWidth($requestData->getScreenWidth());
        $data->setTimezone($requestData->getTimezone());

        $quote = $this->getQuoteById($cartId);
        $this->suiteLogger->orderStartLog('PI', $quote->getReservedOrderId(), $cartId);
        $this->suiteLogger->debugLog($quote->getData(), [__LINE__, __METHOD__]);

        $this->requester->setRequestData($data);
        $this->requester->setQuote($quote);

        return $this->requester->placeOrder();
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        return $this->getQuoteRepository()->get($cartId);
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
