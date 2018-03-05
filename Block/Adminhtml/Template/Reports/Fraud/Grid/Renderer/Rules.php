<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

/**
 * grid block action item renderer
 */
class Rules extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{

    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        /** @var $serializer AdditionalInformation */
        $serializer = \Magento\Framework\App\ObjectManager::getInstance()->get(AdditionalInformation::class);
        $additionalInfo = $serializer->getUnserializedData($row->getData("additional_information"));

        return array_key_exists("fraudrules", $additionalInfo) ? $additionalInfo["fraudrules"] : "";
    }
}
