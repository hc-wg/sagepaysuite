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
            'notificationURL'          => $this->getNotificationUrl($quoteId),
            'transType'                => TransactionType::GOOD_SERVICE_PURCHASE,
            'challengeWindowSize'      => $this->sagepayConfig->getValue("challengewindowsize"),
        ];

        $result['credentialType'] = [
            'cofUsage'      => 'First',
            'initiatedType' => 'CIT',
            'mitType'       => 'Unscheduled'
        ];

        return $result;
    }

    private function getNotificationUrl($quoteId)
    {
        $encryptedQuoteId = $this->encryptAndEncode($quoteId);
        $url = $this->coreUrl->getUrl('sagepaysuite/pi/callback3Dv2', ['_secure' => true, 'quoteId' => $encryptedQuoteId]);

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
        $browserIP = null;
        $clientIp = $this->request->getClientIp();
        $ipAddressesArray = explode(',', $clientIp);

        foreach ($ipAddressesArray as $ipAddress) {
            $ipAddress = trim($ipAddress);

            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $browserIP = $ipAddress;
                break;
            }
        }

        if ($browserIP === null) {
            $browserIP = $this->_getIpvFour($ipAddressesArray);
        }

        return $browserIP;
    }

    /**
     * @param array $ipAddressesArray
     * @return string
     */
    private function _getIpvFour(array $ipAddressesArray)
    {
        $browserIP = $finalIp = '127.0.0.1';
        $ipv4 = '';

        foreach ($ipAddressesArray as $ipAddress) {
            $ipAddress = trim($ipAddress);

            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipFieldsArray = explode(":", $ipAddress);
                $count = 0;

                foreach ($ipFieldsArray as $ipField) {
                    $number = 0;

                    if (strlen($ipField) >= 2) {
                        $subString = substr($ipField, 0, 2);
                        if ($this->_isHexadecimal($subString)) {
                            $number = $this->_hexToInt($subString);
                        }
                    } elseif (strlen($ipField) == 1) {
                        if ($this->_isHexadecimal($ipField)) {
                            $number = $this->_hexToInt($ipField);
                        }
                    }

                    $ipv4 .= $number;

                    if ($count < 3) {
                        $ipv4 .= '.';
                    } elseif ($count == 3) {
                        $finalIp = $ipv4;
                        break;
                    }

                    $count++;
                }

                $browserIP = $finalIp;
                break;
            }
        }

        return $browserIP;
    }

    /**
     * @param $string
     * @return bool
     */
    private function _isHexadecimal($string) {
        return ctype_xdigit($string);
    }

    /**
     * @param $hexadecimal
     * @return int
     */
    private function _hexToInt($hexadecimal) {
        return intval(hexdec($hexadecimal));
    }
}
