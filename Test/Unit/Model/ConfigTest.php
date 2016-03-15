<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $configModel;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this
            ->getMockBuilder('Magento\Framework\App\Config\ScopeConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->configModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Config',
            [
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    public function testIsMethodActive()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM.'/active',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn(true);

        $this->assertEquals(
            true,
            $this->configModel->isMethodActive()
        );
    }

    public function testGetVPSProtocol()
    {
        $this->assertEquals(
            \Ebizmarts\SagePaySuite\Model\Config::VPS_PROTOCOL,
            $this->configModel->getVPSProtocol()
        );
    }

    /**
     * @dataProvider getSagepayPaymentActionDataProvider
     */
    public function testGetSagepayPaymentAction($data)
    {
        $this->configModel->setMethodCode($data["code"]);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.$data["code"].'/payment_action',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn($data["expect"]);

        $this->assertEquals(
            $data["expect"],
            $this->configModel->getSagepayPaymentAction()
        );
    }

    public function getSagepayPaymentActionDataProvider()
    {
        return [
            'test with pi' => [
                [
                    'code' => \Ebizmarts\SagePaySuite\Model\Config::METHOD_PI,
                    'payment_action' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT,
                    'expect' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT_PI
                ]
            ],
            'test without form' => [
                [
                    'code' => \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM,
                    'payment_action' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT,
                    'expect' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT
                ]
            ]
        ];
    }

    /**
     * @dataProvider getPaymentActionDataProvider
     */
    public function testGetPaymentAction($data)
    {
        $this->configModel->setMethodCode($data["code"]);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.$data["code"].'/payment_action',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn($data["expect"]);

        $this->assertEquals(
            $data["expect"],
            $this->configModel->getSagepayPaymentAction()
        );
    }

    public function getPaymentActionDataProvider()
    {
        return [
            'test with payment' => [
                [
                    'code' => \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM,
                    'payment_action' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT,
                    'expect' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE
                ]
            ],
            'test without defer' => [
                [
                    'code' => \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM,
                    'payment_action' => \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER,
                    'expect' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE
                ]
            ]
        ];
    }

    public function testGetVendorname()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/global/vendorname',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('testebizmarts');

        $this->assertEquals(
            'testebizmarts',
            $this->configModel->getVendorname()
        );
    }

    public function testGetLicense()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/global/license',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('f678dfs786fds786dfs876dfs');

        $this->assertEquals(
            'f678dfs786fds786dfs876dfs',
            $this->configModel->getLicense()
        );
    }

    public function testGetStoreDomain()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(\Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_URL,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('http://example.com');

        $this->assertEquals(
            'http://example.com',
            $this->configModel->getStoreDomain()
        );
    }

    public function testGetMode()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/global/mode',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('live');

        $this->assertEquals(
            'live',
            $this->configModel->getMode()
        );
    }

    public function testGetTokenEnabled(){

    }

    public function testGetFormEncryptedPassword()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM.'/encrypted_password',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('345jh345hj45');

        $this->assertEquals(
            '345jh345hj45',
            $this->configModel->getFormEncryptedPassword()
        );
    }

    public function testGetReportingApiUser()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/global/reporting_user',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('ebizmarts');

        $this->assertEquals(
            'ebizmarts',
            $this->configModel->getReportingApiUser()
        );
    }

    public function testGetReportingApiPassword()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/global/reporting_password',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('fds678dsf68ds');

        $this->assertEquals(
            'fds678dsf68ds',
            $this->configModel->getReportingApiPassword()
        );
    }

    public function testGetPIPassword()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI.'/password',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('fd67sf8ds6f78ds6f78ds');

        $this->assertEquals(
            'fd67sf8ds6f78ds6f78ds',
            $this->configModel->getPIPassword()
        );
    }

    public function testGetPIKey()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI.'/key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('fd7s6f87ds6f78ds6f78dsf8ds76f7ds8f687dsf8');

        $this->assertEquals(
            'fd7s6f87ds6f78ds6f78dsf8ds76f7ds8f687dsf8',
            $this->configModel->getPIKey()
        );
    }

    public function testGet3Dsecure()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/advanced/threedsecure',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('Disable');

        $this->assertEquals(
            'Disable',
            $this->configModel->get3Dsecure()
        );
    }

    public function testIsSendBasket(){
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('sagepaysuite/advanced/send_basket',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn(true);

        $this->assertEquals(
            true,
            $this->configModel->isSendBasket()
        );
    }

    public function testGetBasketFormat()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('sagepaysuite/advanced/basket_format',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('Sage50');

        $this->assertEquals(
            'Sage50',
            $this->configModel->getBasketFormat()
        );
    }

    public function testGetPaypalBillingAgreement()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL.'/billing_agreement',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn(false);

        $this->assertEquals(
            false,
            $this->configModel->getPaypalBillingAgreement()
        );
    }

    public function testIsPaypalForceXml()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL.'/force_xml',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn(true);

        $this->assertEquals(
            true,
            $this->configModel->isPaypalForceXml()
        );
    }

    public function testGetNotifyFraudResult()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('sagepaysuite/advanced/fraud_notify',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn('medium_risk');

        $this->assertEquals(
            'medium_risk',
            $this->configModel->getNotifyFraudResult()
        );
    }

    public function testGetAllowedCcTypes()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL.'/cctypes',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn("VI,MC");

        $this->assertEquals(
            "VI,MC",
            $this->configModel->getAllowedCcTypes()
        );
    }

    public function testGetAreSpecificCountriesAllowed()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL.'/allowspecific',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn(0);

        $this->assertEquals(
            0,
            $this->configModel->getAreSpecificCountriesAllowed()
        );
    }

    public function testGetSpecificCountries()
    {
        $this->configModel->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('payment/'.\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL.'/specificcountry',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                NULL)
            ->willReturn("UY,US");

        $this->assertEquals(
            "UY,US",
            $this->configModel->getSpecificCountries()
        );
    }
}
