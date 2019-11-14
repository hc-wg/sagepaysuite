<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 2019-11-08
 * Time: 15:42
 */

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
use \Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

class AddressValidation extends Column
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

    /**
     * @param array $dataSource
     * @return array
     */
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
                            if (isset($additional["avsCvcCheckAddress"])) {
                                $status = $additional["avsCvcCheckAddress"];
                            } else {
                                $status = "NOTPROVIDED";
                            }
                            $addressResult = $this->getAddressResult($status);
                            $url = $this->assetRepository->getUrlWithParams($addressResult, $params);
                            $item[$fieldName . '_src'] = $url;
                        }
                    }
                }
            }
        }
        return $dataSource;
    }

    /**
     * @param string $status
     * @return string
     */
    public function getAddressResult($status)
    {
        $status = strtoupper($status);
        $addressResult = '';
        switch($status){
            case 'MATCHED':
                $addressResult = 'check.png';
                break;
            case 'NOTCHECKED':
                $addressResult = 'outline.png';
                break;
            case 'NOTPROVIDED':
                $addressResult = 'outline.png';
                break;
            case 'NOTMATCHED':
                $addressResult = 'cross.png';
                break;
            case 'PARTIAL':
                $addressResult = 'zebra.png';
                break;
        }
        return self::IMAGE_PATH . $addressResult;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->getData('name');
    }
}
