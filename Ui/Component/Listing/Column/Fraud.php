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
use Ebizmarts\SagePaySuite\Model\Config;
use \Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

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

    /**
     * @var AdditionalInformation
     */
    private $serialize;

    public function __construct(
        Logger $suiteLogger,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        Repository $assetRepository,
        RequestInterface $requestInterface,
        AdditionalInformation $serialize,
        array $components = [],
        array $data = []
    ) {
        $this->suiteLogger      = $suiteLogger;
        $this->orderRepository  = $orderRepository;
        $this->assetRepository  = $assetRepository;
        $this->requestInterface = $requestInterface;
        $this->serialize        = $serialize;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (strpos($item['payment_method'], "sagepaysuite") !== false) {
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
                    $payment = $order->getPayment();

                    if ($payment !== null) {
                        $additional = $payment->getAdditionalInformation();
                        if (is_string($additional)) {
                            $additional = $this->serialize->getUnserializedData($additional);
                        }
                        if (is_array($additional) && !empty($additional)) {
                            $image = $this->getImage($additional);
                            $url = $this->assetRepository->getUrlWithParams($image, $params);
                            $item[$fieldName . '_src'] = $url;
                        }
                    }
                }
            }
        }
        return $dataSource;
    }

    public function getImageNameThirdman($score)
    {
        $image = '';
        if (is_numeric($score)) {
            if ($score < 30) {
                $image = 'check.png';
            } else if ($score >= 30 && $score <= 49) {
                $image = 'zebra.png';
            } else if ($score > 49) {
                $image = 'cross.png';
            }
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


    public function getFieldName()
    {
        return $this->getData('name');
    }

    /**
     * @return string
     */
    public function getTestImage()
    {
        return 'Ebizmarts_SagePaySuite::images/test.png';
    }

    /**
     * @return string
     */
    public function getWaitingImage()
    {
        return 'Ebizmarts_SagePaySuite::images/waiting.png';
    }

    /**
     * @param array $additional
     * @return string
     */
    public function getImage(array $additional)
    {
        if ($this->checkTestModeConfiguration($additional)) {
            $image = $this->getTestImage();
        } else {
            $image = $this->getFraudImage($additional);
        }
        return $image;
    }

    /**
     * @param array $additional
     * @return string
     */
    public function getFraudImage(array $additional)
    {
        if (isset($additional['fraudrules'], $additional['fraudcode'])) {
            $image = $this->getImageNameThirdman($additional['fraudcode']);
        } elseif (isset($additional['fraudcode'])) {
            $image = $this->getImageNameRed($additional['fraudcode']);
        } else {
            $image = $this->getWaitingImage();
        }
        return $image;
    }

    /**
     * @param array $additional
     * @return bool
     */
    public function checkTestModeConfiguration(array $additional)
    {
        return isset($additional["mode"]) && $additional["mode"] === Config::MODE_TEST;
    }
}
