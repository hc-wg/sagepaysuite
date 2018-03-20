<?php
namespace Ebizmarts\SagePaySuite\Plugin;

use Magento\Framework\View\Asset\Minification;

class ExcludeFilesFromMinification
{

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
     * @param callable $proceed
     * @param string $contentType
     * @return string[]
     */
    public function aroundGetExcludes(Minification $subject, callable $proceed, $contentType)
    {
        $result = $proceed($contentType);
        if ($contentType !== 'js') {
            return $result;
        }
        $result[] = 'api/v1/js/sagepay';
        return $result;
    }
}
