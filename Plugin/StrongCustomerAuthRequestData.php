<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Plugin;

use Ebizmarts\SagePaySuite\Model;
use Ebizmarts\SagePaySuite\Model\Config;

class StrongCustomerAuthRequestData
{
    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $sagepayConfig;

    public function __construct(Config $sagepayConfig)
    {
        $this->sagepayConfig = $sagepayConfig;
    }

    /**
     * Exclude Pi remote javascript files from being minified.
     *
     * Using the config node <minify_exclude> is not an option because it does
     * not get merged but overridden by subsequent modules.
     *
     * It will change in Magento 2.3 and merge the values instead of overwriting them
     * https://github.com/magento/magento2/pull/13687
     *
     * @see \Magento\Framework\View\Asset\Minification::XML_PATH_MINIFICATION_EXCLUDES
     *
     * @param Minification $subject
     * @param string[] $result
     * @param string $contentType
     * @return string[]
     */
    public function afterGetRequestData(Model\PiRequest $subject, array $result) : array
    {
        $result['strongCustomerAuthentication'] = [
            'browserJavascriptEnabled' => true,
            'browserJavaEnabled' => '',
            'browserColorDepth' => '',
            'browserScreenHeight' => '',
            'browserScreenWidth' => '',
            'browserTZ' => '',
            'browserAcceptHeader' => '',
            'clientIPAddress' => '',
            'browserLanguage' => '',
            'browserUserAgent' => '',
            //'notificationURL' => '',
            'challengeWindowSize' => $this->sagepayConfig->getValue("challengewindowsize")
        ];

        return $result;
    }
}
