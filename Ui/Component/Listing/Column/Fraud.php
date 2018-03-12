<?php

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

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
    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    public function __construct(
        Logger $suiteLogger,
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
        $this->suiteLogger   = $suiteLogger;
        $this->orderRepository = $orderRepository;
        $this->assetRepository = $assetRepository;
        $this->requestInterface = $requestInterface;
        $this->_helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $fieldName = $this->getData('name');
                $orderId = $item['entity_id'];
                try{
                    $order = $this->orderRepository->get($orderId);
                    $additional = $order->getPayment();
                    if ($additional !== null) {
                        $additional = $additional->getAdditionalInformation();
                        $params = ['_secure' => $this->requestInterface->isSecure()];
                        $image= '';
                        if (isset($additional['fraudrules'], $additional['fraudcode'])) {
                            $image = $this->getImageNameThirdman($additional['fraudcode']);
                        } elseif (isset($additional['fraudcode'])) {
                            $image = $this->getImageNameRed($additional['fraudcode']);
                        }
                        $url = $this->assetRepository->getUrlWithParams($image, $params);
                        $item[$fieldName . '_src'] = $url;
                    }
                }
                catch (\Exception $e){
                    $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);
                }
            }
        }
        return $dataSource;
    }

    public function getImageNameThirdman($score)
    {
        if ($score < 30) {
            $image = 'check.png';
        } else if ($score >= 30 && $score <= 49) {
            $image= 'zebra.png';
        } else {
            $image= 'cross.png';
        }
        return 'Ebizmarts_SagePaySuite::images/icon-shield-'.$image;
    }

    public function getImageNameRed($status)
    {
        $status = strtoupper($status);
        $image = '';
        switch ($status) {
            case 'ACCEPT':
                $image = 'check.png';
                break;
            case 'DENY':
                $image = 'cross.png';
                break;
            case 'CHALLENGE':
                $image = 'zebra.png';
                break;
            case 'NOTCHECKED':
                $image = 'outline.png';
                break;
        }
        return 'Ebizmarts_SagePaySuite::images/icon-shield-'.$image;
    }


}
