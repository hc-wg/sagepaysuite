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
    )
    {

        $this->orderRepository = $orderRepository;
        $this->assetRepository = $assetRepository;
        $this->requestInterface = $requestInterface;
        $this->_helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $fieldName = $this->getData('name');
                $orderId = $item['entity_id'];
                $order = $this->orderRepository->get($orderId);
                $additional = $order->getPayment();
                if ($additional != null) {
                    $additional = $additional->getAdditionalInformation();
                    $params = ['_secure' => $this->requestInterface->isSecure()];

                    if (isset($additional['fraudrules'], $additional['fraudcode'])) {
                        $image = $this->getImageNameT3M($additional['fraudcode']);
                        $url = $this->assetRepository->getUrlWithParams($image, $params);
                        $item[$fieldName . '_src'] = $url;
                    } elseif (isset($additional['fraudcode'])) {
                        $image = $this->getImageNameRED(strtoupper($additional['fraudcode']));
                        $url = $this->assetRepository->getUrlWithParams($image, $params);
                        $item[$fieldName . '_src'] = $url;
                    }
                }
            }
        }
        return $dataSource;
    }

    public function getImageNameT3M($score)
    {
        $image = '';
        if ($score < 30) {
            $image = 'Ebizmarts_SagePaySuite::images/icon-shield-check.png';
        } else if ($score >= 30 && $score <= 49) {
            $image= 'Ebizmarts_SagePaySuite::images/icon-shield-zebra.png';
        } else {
            $image= 'Ebizmarts_SagePaySuite::images/icon-shield-cross.png';
        }
        return $image;
    }

    public function getImageNameRED($status)
    {
        $image = '';
        switch ($status) {
            case 'ACCEPT':
                $image = 'Ebizmarts_SagePaySuite::images/icon-shield-check.png';
                break;
            case 'DENY':
                $image = 'Ebizmarts_SagePaySuite::images/icon-shield-cross.png';
                break;
            case 'CHALLENGE':
                $image = 'Ebizmarts_SagePaySuite::images/icon-shield-zebra.png';
                break;
            case 'NOTCHECKED':
                $image = 'Ebizmarts_SagePaySuite::images/icon-shield-outline.png';
                break;
        }
        return $image;
    }


}
