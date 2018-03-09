<?php

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;

class Fraud extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Repository
     */
    private $assetRepository;
    /**
     * @var RequestInterface
     */
    protected $requestInterface;
    /**
     * @var Data
     */
    private $_helper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        Repository $assetRepository,
        RequestInterface $requestInterface,
        Data $helper,
        array $components = [],
        array $data = []
    ) {

        $this->orderRepository = $orderRepository;
        $this->assetRepository = $assetRepository;
        $this->requestInterface= $requestInterface;
        $this->_helper          = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $fieldName = $this->getData('name');

                $orderId = $item['entity_id'];
                $order= $this->orderRepository->get($orderId);
                $additional = $order->getPayment();
                if($additional != null){
                    $additional = $additional->getAdditionalInformation();

                    if(isset($additional['fraudrules']) && isset($additional['fraudcode'])){
                        $score = $additional['fraudcode'];
                        if ($score < 30) {
                            $params = ['_secure' => $this->requestInterface->isSecure()];
                            $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-check.png', $params);
                            $item[$fieldName . '_src'] = $url;
                        } else if ($score >= 30 && $score <= 49) {
                            $params = ['_secure' => $this->requestInterface->isSecure()];
                            $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-zebra.png', $params);
                            $item[$fieldName . '_src'] = $url;
                        } else {
                            $params = ['_secure' => $this->requestInterface->isSecure()];
                            $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-cross.png', $params);
                            $item[$fieldName . '_src'] = $url;
                        }
                    }

                    elseif(isset($additional['fraudcode'])){
                        $status = $additional['fraudcode'];
                        switch (strtoupper($status)) {
                            case 'ACCEPT':
                                $params = ['_secure' => $this->requestInterface->isSecure()];
                                $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-check.png', $params);
                                $item[$fieldName . '_src'] = $url;
                                break;
                            case 'DENY':
                                $params = ['_secure' => $this->requestInterface->isSecure()];
                                $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-cross.png', $params);
                                $item[$fieldName . '_src'] = $url;
                                break;
                            case 'CHALLENGE':
                                $params = ['_secure' => $this->requestInterface->isSecure()];
                                $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-zebra.png', $params);
                                $item[$fieldName . '_src'] = $url;
                                break;
                            case 'NOTCHECKED':
                                $params = ['_secure' => $this->requestInterface->isSecure()];
                                $url = $this->assetRepository->getUrlWithParams('Ebizmarts_SagePaySuite::images/icon-shield-outline.png', $params);
                                $item[$fieldName . '_src'] = $url;
                                break;
                        }
                    }


                }
            }

        }

        return $dataSource;
    }

}
