<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 * @package Ebizmarts\SagePaySuite\Model\Config\Source
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT,
                'label' => __('Payment - Authorize and Capture'),
            ],
            [
                'value' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER,
                'label' => __('Defer - Authorize Only'),
            ],
            [
                'value' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE,
                'label' => __('Authorize - Authorize Only'),
            ]
        ];
    }
}
