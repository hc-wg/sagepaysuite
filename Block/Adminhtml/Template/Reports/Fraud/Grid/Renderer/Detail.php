<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

/**
 * grid block action item renderer
 */
class Detail extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
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

        return array_key_exists("fraudcodedetail",$additionalInfo) ? $additionalInfo["fraudcodedetail"] : "";
    }

}
