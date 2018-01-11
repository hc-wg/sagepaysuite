<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

/**
 * grid block action item renderer
 */
class OrderId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderFactory = $orderFactory;
    }

    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $orderId = parent::render($row);

        //find order with quote id
        $order = $this->orderFactory->create()->load($orderId);

        $link = $this->getUrl('sales/order/view/', ['order_id'=>$order->getEntityId()]);

        return '<a href="' . $link . '">' . $order->getIncrementId() . '</a>';
    }
}
