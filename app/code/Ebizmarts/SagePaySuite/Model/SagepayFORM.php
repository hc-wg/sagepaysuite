<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

/**
 * Class SagepayFORM
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class SagepayFORM extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_SAGEPAY_FORM_CODE = 'sagepaysuite_form';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_SAGEPAY_FORM_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = 'Ebizmarts\SagePaySuite\Block\Form\SagepayFORM';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info\SagepayFORM';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;




}
