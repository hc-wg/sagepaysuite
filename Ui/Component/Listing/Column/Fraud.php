<?php

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\StoreManagerInterface;

class Fraud extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteria;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_requestInterfase;
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_helper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    private $_logger;

    /**
     * Monkey constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param SearchCriteriaBuilder $criteria
     * @param \Ebizmarts\SagePaySuite\Helper\Data $helper
     *

     * @param array $components
     * @param array $data
     * @param Logger $suiteLogger
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\App\RequestInterface $requestInterface,
        SearchCriteriaBuilder $criteria,
         \Ebizmarts\SagePaySuite\Helper\Data $helper,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,

        array $components = [],
        array $data = []
    ) {

        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->_assetRepository = $assetRepository;
        $this->_requestInterfase= $requestInterface;
        $this->_helper          = $helper;
        $this->suiteLogger        = $suiteLogger;
        $this->_logger          = $logger;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $fieldName = $this->getData('name');

                $orderId = $item['entity_id'];
                $order= $this->_orderRepository->get($orderId);
                $additional = $order->getPayment()->getAdditionalInformation();

                if(isset($additional['fraudcode']) && isset($additional['fraudrules']) ){
                    $score = $additional['fraudcode'];
                    if ($score < 30) {
                        $params = ['_secure' => $this->_requestInterfase->isSecure()];
                        $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-check.png', $params);
                        $item[$fieldName . '_src'] = $url;
                    } else if ($score >= 30 && $score <= 49) {
                        $params = ['_secure' => $this->_requestInterfase->isSecure()];
                        $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-zebra.png', $params);
                        $item[$fieldName . '_src'] = $url;
                    } else {
                        $params = ['_secure' => $this->_requestInterfase->isSecure()];
                        $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-cross.png', $params);
                        $item[$fieldName . '_src'] = $url;
                    }
                }

                elseif(isset($additional['fraudcode'])){
                    $status = $additional['fraudcode'];
                    switch (strtoupper($status)) {
                        case 'ACCEPT':
                            $params = ['_secure' => $this->_requestInterfase->isSecure()];
                            $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-check.png', $params);
                            $item[$fieldName . '_src'] = $url;
                            break;
                        case 'DENY':
                            $params = ['_secure' => $this->_requestInterfase->isSecure()];
                            $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-cross.png', $params);
                            $item[$fieldName . '_src'] = $url;
                            break;
                        case 'CHALLENGE':
                            $params = ['_secure' => $this->_requestInterfase->isSecure()];
                            $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-zebra.png', $params);
                            $item[$fieldName . '_src'] = $url;
                            break;
                        case 'NOTCHECKED':
                            $params = ['_secure' => $this->_requestInterfase->isSecure()];
                            $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-outline.png', $params);
                            $item[$fieldName . '_src'] = $url;
                            break;
                    }
                }


            }
        }

        return $dataSource;
    }
}
