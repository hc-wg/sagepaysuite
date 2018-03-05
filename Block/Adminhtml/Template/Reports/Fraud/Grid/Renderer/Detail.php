<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text;
use Magento\Framework\DataObject;
use Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

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
        /** @var $serializer AdditionalInformation */
        $serializer = \Magento\Framework\App\ObjectManager::getInstance()->get(AdditionalInformation::class);
        $additionalInfo = $serializer->getUnserializedData($row->getData("additional_information"));

        return array_key_exists("fraudcodedetail", $additionalInfo) ? $additionalInfo["fraudcodedetail"] : "";
    }
}
