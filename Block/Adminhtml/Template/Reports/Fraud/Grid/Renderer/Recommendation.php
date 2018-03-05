<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

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
        /** @var $serializer AdditionalInformation */
        $serializer = \Magento\Framework\App\ObjectManager::getInstance()->get(AdditionalInformation::class);
        $additionalInfo = $serializer->getUnserializedData($row->getData("additional_information"));

        $html = "";

        if (array_key_exists("fraudscreenrecommendation", $additionalInfo)) {
            $html = $additionalInfo["fraudscreenrecommendation"];
        }

        switch ($html) {
            case Config::REDSTATUS_CHALLENGE:
            case Config::T3STATUS_HOLD:
                $html = '<span style="color:orange;">' . $html . '</span>';
                break;
            case Config::REDSTATUS_DENY:
            case Config::T3STATUS_REJECT:
                $html = '<span style="color:red;">' . $html . '</span>';
                break;
        }

        return $html;
    }
}
