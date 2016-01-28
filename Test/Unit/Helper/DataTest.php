<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;

class DataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->storeManagerMock = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->getMockForAbstractClass();

        $this->dataHelper = $helper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Data',
            ['storeManager' => $this->storeManagerMock]
        );
    }

    public function testClearTransactionId()
    {
        $uncleanTransactionId = self::TEST_VPSTXID . "-capture";

        $this->assertSame(
            self::TEST_VPSTXID,
            $this->dataHelper->clearTransactionId($uncleanTransactionId)
        );
    }


}
