<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Ebizmarts\SagePaySuite\Block\Customer;

/**
 * Block to display customer tokens in customer area
 */
class TokenList extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\Downloadable\Model\ResourceModel\Link\Purchased\CollectionFactory
     */
    protected $_linksFactory;

    /**
     * @var \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory
     */
    protected $_itemsFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    protected $_tokenModel;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Downloadable\Model\ResourceModel\Link\Purchased\CollectionFactory $linksFactory
     * @param \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Downloadable\Model\ResourceModel\Link\Purchased\CollectionFactory $linksFactory,
        \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currentCustomer = $currentCustomer;
        $this->_linksFactory = $linksFactory;
        $this->_itemsFactory = $itemsFactory;
        $this->_config = $config;
        $this->_tokenModel = $tokenModel;

        $this->setItems($this->_tokenModel->getCustomerTokens($this->currentCustomer->getCustomerId(),
            $this->_config->getVendorname()));
    }

    /**
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

//        $pager = $this->getLayout()->createBlock(
//            'Magento\Theme\Block\Html\Pager',
//            'downloadable.customer.products.pager'
//        )->setCollection(
//            $this->getItems()
//        )->setPath('downloadable/customer/products');
//        $this->setChild('pager', $pager);
//        $this->getItems()->load();
//        foreach ($this->getItems() as $item) {
//            $item->setPurchased($this->getPurchased()->getItemById($item->getPurchasedId()));
//        }
        return $this;
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRefererUrl()) {
            return $this->getRefererUrl();
        }
        return $this->getUrl('customer/account/');
    }

}
