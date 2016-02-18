<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

/**
 * grid block action item renderer
 */
class Recommendation extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{

    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $additionalInfo = $row->getData("additional_information");
        if (!empty($additionalInfo)) {
            $additionalInfo = unserialize($additionalInfo);
        }

        $html = array_key_exists("fraudscreenrecommendation", $additionalInfo) ? $additionalInfo["fraudscreenrecommendation"] : "";

        switch ($html) {
            case \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_CHALLENGE:
            case \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_HOLD:
                $html = '<span style="color:orange;">' . $html . '</span>';
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_DENY:
            case \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT:
                $html = '<span style="color:red;">' . $html . '</span>';
                break;
        }

        return $html;
    }

}
