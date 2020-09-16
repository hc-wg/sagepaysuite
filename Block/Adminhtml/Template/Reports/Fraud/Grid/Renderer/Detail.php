<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text;
use Magento\Framework\DataObject;

/**
 * grid block action item renderer
 */
class Detail extends Text
{

    /**
     * Render grid column
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $additionalInfo = $row->getData("additional_information");
        if (!empty($additionalInfo)) {
            $additionalInfo = unserialize($additionalInfo); //@codingStandardsIgnoreLine
        }

        return isset($additionalInfo["fraudcodedetail"]) ? $additionalInfo["fraudcodedetail"] : "";
    }
}
