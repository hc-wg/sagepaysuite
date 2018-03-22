<?php
declare(strict_types=1);

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
     * @param string[] $result
     * @param string $contentType
     * @return string[]
     */
    public function afterGetExcludes(Minification $subject, array $result, string $contentType) : array
    {
        if ($contentType === 'js') {
            $result[] = 'api/v1/js/sagepay';
        }

        return $result;
    }
}
