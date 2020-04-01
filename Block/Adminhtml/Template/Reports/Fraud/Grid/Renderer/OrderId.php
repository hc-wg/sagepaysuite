<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

/**
 * grid block action item renderer
 */
class OrderId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{

    /**
     * @var OrderRepository
     */
    private $_orderRepository;

    /**
     * OrderId constructor.
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_orderRepository = $orderRepository;
    }

    /**
     * Render grid row
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $orderId = parent::render($row);

        try {
            //Find order by order id
            $order = $this->_orderRepository->get($orderId);
        } catch (NoSuchEntityException $exception) {
            return '';
        } catch (InputException $exception) {
            return '';
        }

        $link = $this->getUrl('sales/order/view/', ['order_id' => $order->getEntityId()]);

        return '<a href="' . $link . '">' . $order->getIncrementId() . '</a>';
    }
}
