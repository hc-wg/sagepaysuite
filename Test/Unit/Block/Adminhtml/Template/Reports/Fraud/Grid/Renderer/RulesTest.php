<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class RulesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Rules
     */
    private $rulesRendererBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->rulesRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Rules',
            []
        );

        $this->rulesRendererBlock->setColumn($columnMock);
    }
    // @codingStandardsIgnoreEnd

    public function testRenderEmpty()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->with('additional_information')
            ->willReturn([]);

        $this->assertEquals(
            '',
            $this->rulesRendererBlock->render($rowMock)
        );
    }

    public function testRenderNotEmpty()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->with('additional_information')
            ->willReturn('a:1:{s:10:"fraudrules";a:0:{}}');

        $this->assertEquals(
            '<ul></ul>',
            $this->rulesRendererBlock->render($rowMock)
        );
    }

    public function testRenderNotEmptyArray()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->with('additional_information')
            ->willReturn('a:14:{s:8:"cc_last4";N;s:20:"merchant_session_key";N;s:15:"card_identifier";N;s:10:"statusCode";s:4:"2007";s:12:"statusDetail";s:75:"Please redirect your customer to the ACSURL to complete the 3DS Transaction";s:4:"moto";b:0;s:10:"vendorname";s:10:"activeplus";s:4:"mode";s:4:"test";s:13:"paymentAction";s:8:"Deferred";s:12:"bankAuthCode";N;s:8:"txAuthNo";N;s:12:"vendorTxCode";s:37:"000000000-2018-12-14-0000000000000000";s:12:"method_title";s:15:"Sage Pay Direct";s:10:"fraudrules";a:13:{i:0;a:2:{s:11:"description";s:44:"Delivery surname is within the email address";s:5:"score";s:3:"-11";}i:1;a:2:{s:11:"description";s:30:"Telephone number is a landline";s:5:"score";s:2:"-3";}i:2;a:2:{s:11:"description";s:46:"Delivery address or email domain is a business";s:5:"score";s:3:"-10";}i:3;a:2:{s:11:"description";s:56:"Card verification code passed [Amount less than 1000000]";s:5:"score";s:3:"-10";}i:4;a:2:{s:11:"description";s:51:"Bank address check match [Amount less than 1000000]";s:5:"score";s:2:"-6";}i:5;a:2:{s:11:"description";s:52:"Bank Postcode check Match [Amount less than 1000000]";s:5:"score";s:2:"-6";}i:6;a:2:{s:11:"description";s:131:"Number of purchases at delivery address exceeds lower threshold [More than 2 purchases at the delivery address in the last 14 days]";s:5:"score";s:1:"2";}i:7;a:2:{s:11:"description";s:132:"Number of purchases at delivery address exceeds medium threshold [More than 4 purchases at the delivery address in the last 14 days]";s:5:"score";s:1:"3";}i:8;a:2:{s:11:"description";s:132:"Number of purchases at delivery address exceeds higher threshold [More than 6 purchases at the delivery address in the last 14 days]";s:5:"score";s:1:"3";}i:9;a:2:{s:11:"description";s:165:"Recent spend at delivery address exceeds lower threshold [More than 1 purchases at the delivery address in the last 30 days with a total spend of greater than 50000]";s:5:"score";s:1:"5";}i:10;a:2:{s:11:"description";s:167:"Recent spend at delivery address exceeds medium threshold [More than 1 purchases at the delivery address in the last 30 days with a total spend of greater than 100000]";s:5:"score";s:1:"5";}i:11;a:2:{s:11:"description";s:129:"Number of purchases at billing address exceeds lower threshold [More than 1 purchases at the billing address in the last 14 days]";s:5:"score";s:1:"2";}i:12;a:2:{s:11:"description";s:160:"Recent spend at billing address exceeds lower threshold [More than 1 purchases at the billing address in the last 30 days with a total spend greater than 50000]";s:5:"score";s:1:"5";}}}');

        $resultRender = $this->rulesRendererBlock->render($rowMock);

        $this->assertEquals('<ul><li>Delivery surname is within the email address <strong>(score: -11)</strong></li><li>Telephone number is a landline <strong>(score: -3)</strong></li><li>Delivery address or email domain is a business <strong>(score: -10)</strong></li><li>Card verification code passed [Amount less than 1000000] <strong>(score: -10)</strong></li><li>Bank address check match [Amount less than 1000000] <strong>(score: -6)</strong></li><li>Bank Postcode check Match [Amount less than 1000000] <strong>(score: -6)</strong></li><li>Number of purchases at delivery address exceeds lower threshold [More than 2 purchases at the delivery address in the last 14 days] <strong>(score: 2)</strong></li><li>Number of purchases at delivery address exceeds medium threshold [More than 4 purchases at the delivery address in the last 14 days] <strong>(score: 3)</strong></li><li>Number of purchases at delivery address exceeds higher threshold [More than 6 purchases at the delivery address in the last 14 days] <strong>(score: 3)</strong></li><li>Recent spend at delivery address exceeds lower threshold [More than 1 purchases at the delivery address in the last 30 days with a total spend of greater than 50000] <strong>(score: 5)</strong></li><li>Recent spend at delivery address exceeds medium threshold [More than 1 purchases at the delivery address in the last 30 days with a total spend of greater than 100000] <strong>(score: 5)</strong></li><li>Number of purchases at billing address exceeds lower threshold [More than 1 purchases at the billing address in the last 14 days] <strong>(score: 2)</strong></li><li>Recent spend at billing address exceeds lower threshold [More than 1 purchases at the billing address in the last 30 days with a total spend greater than 50000] <strong>(score: 5)</strong></li></ul>', $resultRender);
    }
}
