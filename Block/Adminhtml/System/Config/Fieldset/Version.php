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
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
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

    public function getPxParams()
    {
        $extension = "Sage Pay Suite M2;{$this->getVersion()}";
        $mageEdition = $this->_metaData->getEdition();
        switch($mageEdition)
        {
            case 'Community':
                $mageEdition = 'CE';
                break;
            case 'Enterprise':
                $mageEdition = 'EE';
                break;
        }
        $mageVersion = $this->_metaData->getVersion();
        $mage = "Magento {$mageEdition};{$mageVersion}";
        $hash = md5($extension . '_' . $mage . '_' . $extension);

        return "ext=$extension&mage={$mage}&ctrl={$hash}";
    }
}