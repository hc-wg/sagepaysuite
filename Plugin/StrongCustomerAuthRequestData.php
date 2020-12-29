<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Plugin;

use Ebizmarts\SagePaySuite\Api\Data\ScaTransType as TransactionType;
use Ebizmarts\SagePaySuite\Model;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;

class StrongCustomerAuthRequestData
{
    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $sagepayConfig;

    /** @var \Zend\Http\PhpEnvironment\Request */
    private $request;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $coreUrl;

    public function __construct(
        Model\Config $sagepayConfig,
        \Magento\Framework\HTTP\PhpEnvironment\Request $request,
        \Magento\Framework\UrlInterface $coreUrl,
        Model\CryptAndCodeData $cryptAndCode
    ) {
        $this->sagepayConfig = $sagepayConfig;
        $this->request       = $request;
        $this->coreUrl       = $coreUrl;
        $this->cryptAndCode  = $cryptAndCode;
    }

    /**
     * Exclude Pi remote javascript files from being minified.

     * @param \Ebizmarts\SagePaySuite\Model\PiRequest $subject
     * @param string[] $result
     * @return string[]
     */
    public function afterGetRequestData($subject, array $result) : array
    {
        if (!$this->sagepayConfig->shouldUse3dV2()) {
            return $result;
        }

        /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequest $data */
        $data = $subject->getRequest();
        $quoteId = $subject->getCart()->getId();

        /** @var $subject \Ebizmarts\SagePaySuite\Model\PiRequest */
        $result['strongCustomerAuthentication'] = [
            'browserJavascriptEnabled' => 1,
            'browserJavaEnabled'       => $data->getJavaEnabled(),
            'browserColorDepth'        => $this->getBrowserColorDepth($data),
            'browserScreenHeight'      => $data->getScreenHeight(),
            'browserScreenWidth'       => $data->getScreenWidth(),
            'browserTZ'                => $data->getTimezone(),
            'browserAcceptHeader'      => $this->request->getHeader('Accept'),
            'browserIP'                => $this->getBrowserIP(),
            'browserLanguage'          => $data->getLanguage(),
            'browserUserAgent'         => $data->getUserAgent(),
            'notificationURL'          => $this->getNotificationUrl($quoteId, $data->getSaveToken()),
            'transType'                => TransactionType::GOOD_SERVICE_PURCHASE,
            'challengeWindowSize'      => $this->sagepayConfig->getValue("challengewindowsize"),
        ];
        if ($data->getSaveToken() || $data->getReusableToken()) {
            $result['credentialType'] = [
                'cofUsage'      => $this->getCofUsage($data),
                'initiatedType' => 'CIT'
            ];
        }

        return $result;
    }

    /**
     * @param int $quoteId
     * @param bool $saveToken
     * @return string
     */
    private function getNotificationUrl($quoteId, $saveToken)
    {
        $encryptedQuoteId = $this->encryptAndEncode($quoteId);
        $url = $this->coreUrl->getUrl(
            'sagepaysuite/pi/callback3Dv2',
            ['_secure' => true, 'quoteId' => $encryptedQuoteId, 'saveToken' => $saveToken]
        );

        return $url;
    }

    /**
     * @param $data
     * @return string
     */
    private function encryptAndEncode($data)
    {
        return $this->cryptAndCode->encryptAndEncode($data);
    }

    /**
     * @param $data \Ebizmarts\SagePaySuite\Api\Data\PiRequest
     * @return string
     */
    private function getCofUsage($data)
    {
        return $data->getReusableToken() ? 'Subsequent' : 'First';
    }

    /**
     * @param \Ebizmarts\SagePaySuite\Api\Data\PiRequest $data
     * @return int
     */
    private function getBrowserColorDepth(\Ebizmarts\SagePaySuite\Api\Data\PiRequest $data)
    {
        $colorDepth = $data->getColorDepth();
        $colorDepth = $colorDepth == 30 ? 24 : $colorDepth;

        return $colorDepth;
    }

    /**
     * @return mixed|string
     */
    private function getBrowserIP()
    {
        $browserIP = $this->request->getClientIp();
        $ipAddressesArray = explode(',', $browserIP);

        if (!empty($ipAddressesArray)) {
            $browserIP = $ipAddressesArray[0];
        }

        foreach ($ipAddressesArray as $ipAddress) {
            $ipAddress = trim($ipAddress);

            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $browserIP = $ipAddress;
                break;
            }
        }

        return $browserIP;
    }
}
