<?php

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Fraud extends Column
{
    const IMAGE_PATH = 'Ebizmarts_SagePaySuite::images/icon-shield-';

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
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    public function __construct(
        Logger $suiteLogger,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        Repository $assetRepository,
        RequestInterface $requestInterface,
        array $components = [],
        array $data = []
    )
    {
        $this->suiteLogger = $suiteLogger;
        $this->orderRepository = $orderRepository;
        $this->assetRepository = $assetRepository;
        $this->requestInterface = $requestInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $fieldName = $this->getFieldName();
                $orderId = $item['entity_id'];
                $params = ['_secure' => $this->requestInterface->isSecure()];
                try {
                    $order = $this->orderRepository->get($orderId);
                } catch (InputException $e) {
                    $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);
                    continue;
                } catch (NoSuchEntityException $e) {
                    $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);
                    continue;
                }
                $additional = $order->getPayment();
                if ($additional !== null) {
                    $additional = $additional->getAdditionalInformation();
                    $image = '';
                    if (isset($additional['fraudrules'], $additional['fraudcode'])) {
                        $image = $this->getImageNameThirdman($additional['fraudcode']);
                    } elseif (isset($additional['fraudcode'])) {
                        $image = $this->getImageNameRed($additional['fraudcode']);
                    }
                    $url = $this->assetRepository->getUrlWithParams($image, $params);
                    $item[$fieldName . '_src'] = $url;
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
            $image = 'zebra.png';
        } else {
            $image = 'cross.png';
        }
        return self::IMAGE_PATH . $image;
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
        return self::IMAGE_PATH . $image;
    }

    /**
     * @return mixed
     */
    private function getFieldName(): mixed
    {
        return $this->getData('name');
    }


}
