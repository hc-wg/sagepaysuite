<?php
/**
 * Copyright © 2019 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class OrderGridInfo
{
    const IMAGE_PATH = 'Ebizmarts_SagePaySuite::images/icon-shield-';

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    /**
     * @var Repository
     */
    private $assetRepository;

    /**
     * OrderGridInfo constructor.
     * @param RequestInterface $requestInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $suiteLogger
     * @param Repository $assetRepository
     */
    public function __construct(
        RequestInterface $requestInterface,
        OrderRepositoryInterface $orderRepository,
        Logger $suiteLogger,
        Repository $assetRepository
    ) {
        $this->requestInterface = $requestInterface;
        $this->orderRepository = $orderRepository;
        $this->suiteLogger = $suiteLogger;
        $this->assetRepository = $assetRepository;
    }

    /**
     * @param array $dataSource
     * @param string $index
     * @param string $fieldName
     * @return array
     */
    public function prepareColumn(array $dataSource, $index, $fieldName)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (strpos($item['payment_method'], "sagepaysuite") !== false) {
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
                            $additional = @unserialize($additional); //@codingStandardsIgnoreLine
                        }
                        if (is_array($additional) && !empty($additional)) {
                            $status = $this->getStatus($additional, $index);
                            $image = $this->getImage($index, $status);
                            $url = $this->assetRepository->getUrlWithParams($image, $params);
                            $item[$fieldName . '_src'] = $url;
                            $item[$fieldName . '_alt'] = $status;
                        }
                    }
                }
            }
        }
        return $dataSource;
    }

    /**
     * @param $index
     * @param $status
     * @return string
     */
    public function getImage($index, $status)
    {
        if ($index == "threeDStatus") {
            $image = $this->getThreeDStatus($status);
        } else {
            $image = $this->getStatusImage($status);
        }

        return $image;
    }
    /**
     * @param $status
     * @return string
     */
    public function getThreeDStatus($status)
    {
        $status = strtoupper($status);
        switch($status){
            case 'AUTHENTICATED':
                $threeDStatus = 'check.png';
                break;
            case 'NOTCHECKED':
            case 'NOTAUTHENTICATED':
            case 'CARDNOTENROLLED':
            case 'ISSUERNOTENROLLED':
            case 'ATTEMPTONLY':
            case 'NOTAVAILABLE':
            case 'INCOMPLETE':
            default:
                $threeDStatus = 'outline.png';
                break;
            case 'ERROR':
            case 'MALFORMEDORINVALID':
                $threeDStatus = 'cross.png';
                break;
        }
        return self::IMAGE_PATH . $threeDStatus;
    }

    /**
     * @param $status
     * @return string
     */
    public function getStatusImage($status)
    {
        $status = strtoupper($status);
        switch($status){
            case 'MATCHED':
                $imageUrl = 'check.png';
                break;
            case 'NOTCHECKED':
            case 'NOTPROVIDED':
            default:
                $imageUrl = 'outline.png';
                break;
            case 'NOTMATCHED':
                $imageUrl = 'cross.png';
                break;
            case 'PARTIAL':
                $imageUrl = 'zebra.png';
                break;
        }

        return self::IMAGE_PATH . $imageUrl;
    }

    /**
     * @param $additional
     * @param $index
     * @return string
     */
    public function getStatus($additional, $index)
    {
        if (isset($additional[$index])) {
            $status = $additional[$index];
        } else {
            $status = "NOTPROVIDED";
        }

        return $status;
    }
}
