<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset;

class Version extends \Magento\Backend\Block\Template implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'Ebizmarts_SagePaySuite::system/config/fieldset/version.phtml';
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_metaData;
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * @param \Magento\Framework\Module\ModuleList\Loader $loader
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_metaData = $productMetaData;
        $this->_suiteHelper = $suiteHelper;
    }
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->toHtml();
    }

    public function getVersion()
    {
        return $this->_suiteHelper->getVersion();
    }
}