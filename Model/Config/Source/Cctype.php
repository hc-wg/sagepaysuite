<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Config\Source;

/**
 * Class Cctype
 * @package Ebizmarts\SagePaySuite\Model\Config\Source
 */
class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'MI', 'AE', 'DN', 'JCB');
    }
}