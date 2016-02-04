<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Tokens\Grid\Renderer;

/**
 * grid block action item renderer
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $actions = [];

        $actions[] = [
            'url' => $this->getUrl('*/*/delete', ['id' => $row->getId()]),
            'caption' => __('Delete'),
        ];

//        $actions[] = [
//            'url' => $this->getUrl('adminhtml/*/preview', ['id' => $row->getId()]),
//            'popup' => false,
//            'caption' => __('Verify'),
//        ];

        $this->getColumn()->setActions($actions);

        return parent::render($row);
    }

}
