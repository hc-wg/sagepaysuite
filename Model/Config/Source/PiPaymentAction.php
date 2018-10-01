<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 10/1/18
 * Time: 5:07 PM
 */

namespace Ebizmarts\SagePaySuite\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Ebizmarts\SagePaySuite\Model\Config;

/**
 * Class PaymentAction
 * @package Ebizmarts\SagePaySuite\Model\Config\Source
 */
class PiPaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Config::ACTION_PAYMENT_PI,
                'label' => __('Payment - Authorize and Capture'),
            ]
        ];
    }
}