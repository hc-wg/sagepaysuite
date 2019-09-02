<?php

namespace Ebizmarts\SagePaySuite\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Protocol
 * @package Ebizmarts\SagePaySuite\Model\Config\Source
 */
class Protocol implements ArrayInterface
{

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '3.00',
                'label' => __('3.00'),
            ],
            [
                'value' => '4.00',
                'label' => __('4.00 (3Dv2)')
            ]
        ];
    }
}
