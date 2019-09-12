<?php
namespace Ebizmarts\SagePaySuite\Plugin;

use Ebizmarts\SagePaySuite\Api\Data\ScaTransType as TransactionType;
use Ebizmarts\SagePaySuite\Model;

class StrongCustomerAuthRequestData
{
    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $sagepayConfig;

    /** @var \Zend\Http\PhpEnvironment\Request */
    private $request;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $coreUrl;

    public function __construct(
        Model\Config $sagepayConfig,
        \Magento\Framework\HTTP\PhpEnvironment\Request $request,
        \Magento\Framework\UrlInterface $coreUrl
    ) {
        $this->sagepayConfig = $sagepayConfig;
        $this->request       = $request;
        $this->coreUrl       = $coreUrl;
    }

    /**
     * Exclude Pi remote javascript files from being minified.

     * @param \Ebizmarts\SagePaySuite\Model\PiRequest $subject
     * @param string[] $result
     * @return string[]
     */
    public function afterGetRequestData($subject, array $result)
    {
        if (!$this->sagepayConfig->shouldUse3dV2()) {
            return $result;
        }

        /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequest $data */
        $data = $subject->getRequest();

        /** @var $subject \Ebizmarts\SagePaySuite\Model\PiRequest */
        $result['strongCustomerAuthentication'] = [
            'browserJavascriptEnabled' => 1,
            'browserJavaEnabled'       => $data->getJavaEnabled(),
            'browserColorDepth'        => $data->getColorDepth(),
            'browserScreenHeight'      => $data->getScreenHeight(),
            'browserScreenWidth'       => $data->getScreenWidth(),
            'browserTZ'                => $data->getTimezone(),
            'browserAcceptHeader'      => $this->request->getHeader('Accept'),
            'browserIP'                => $this->request->getClientIp(),
            'browserLanguage'          => $data->getLanguage(),
            'browserUserAgent'         => $data->getUserAgent(),
            'notificationURL'          => $this->getNotificationUrl(),
            'transType'                => TransactionType::GOOD_SERVICE_PURCHASE,
            'challengeWindowSize'      => $this->sagepayConfig->getValue("challengewindowsize"),
        ];

        return $result;
    }

    private function getNotificationUrl()
    {
        $url = $this->coreUrl->getUrl('sagepaysuite/pi/callback3Dv2', ['_secure' => true]);

        return $url;
    }
}
