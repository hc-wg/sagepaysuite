<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\Template;

class Version extends Template implements RendererInterface
{
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $_metaData;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_metaData    = $productMetaData;
        $this->_suiteHelper = $suiteHelper;
    }
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        if ($element->getData('group')['id'] == 'version') {
            $html = $this->toHtml();
        }
        return $html;
    }

    public function getVersion()
    {
        return $this->_suiteHelper->getSagePaySuiteModuleVersionNumber();
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'Ebizmarts_SagePaySuite::system/config/fieldset/version.phtml';
    }
}
