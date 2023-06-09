<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Logger;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger;

class LoggerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider sageLogDataProvider
     */
    public function testSageLog($data)
    {
        $requestHandlerMock = $this
            ->getMockBuilder(Logger\Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exceptionHandlerMock = $this
            ->getMockBuilder(Logger\Exception::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cronHandlerMock = $this
            ->getMockBuilder(Logger\Cron::class)
            ->disableOriginalConstructor()
            ->getMock();
        $debugHandlerMock = $this
            ->getMockBuilder(Logger\Debug::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handlers = [
            $requestHandlerMock,
            $cronHandlerMock,
            $exceptionHandlerMock,
            $debugHandlerMock
        ];
        $name = "SagePaySuiteLogger";

        $configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock = $this
            ->getMockBuilder(Logger\Logger::class)
            ->setMethods(['addRecord'])
            ->setConstructorArgs(
                [
                    'config'      => $configMock,
                    'suiteHelper' => $suiteHelperMock,
                    'name'        => $name,
                    'handlers'    => $handlers
                ]
            )
            ->getMock();

        $suiteHelperMock
            ->expects($this->exactly($data['removalPersonalInformationExpects']))
            ->method($data['removalPersonalInformationMethod'])
            ->with($data['message'])
            ->willReturn($data['removalPersonalInformationReturn']);
        $loggerMock
            ->expects($this->once())
            ->method('addRecord')
            ->with($data["type"], $data["message_p"] . "\r\n", $data['context'])
            ->willReturn(true);

        $this->assertTrue($loggerMock->sageLog($data["type"], $data["message"], $data['context']));
    }

    public function sageLogDataProvider()
    {
        return [
            'test null' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => null,
                    'message_p' => "NULL",
                    'context'   => ['Zarata', 34],
                    'removalPersonalInformationReturn' => [],
                    'removalPersonalInformationExpects' => 0,
                    'removalPersonalInformationMethod' => 'removePersonalInformation'
                ]
            ],
            'test string' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => "ERROR TEST",
                    'message_p' => "ERROR TEST",
                    'context'   => [],
                    'removalPersonalInformationReturn' => [],
                    'removalPersonalInformationExpects' => 0,
                    'removalPersonalInformationMethod' => 'removePersonalInformation'
                ]
            ],
            'test array' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => ["error" => true],
                    'message_p' => json_encode(["error" => true], JSON_PRETTY_PRINT),
                    'context'   => [],
                    'removalPersonalInformationReturn' => ["error" => true],
                    'removalPersonalInformationExpects' => 1,
                    'removalPersonalInformationMethod' => 'removePersonalInformation'
                ]
            ],
            'test object' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => (object)["error" => true],
                    'message_p' => json_encode(((object)["error" => true]), JSON_PRETTY_PRINT),
                    'context'   => ['MyClass\\Test', 69],
                    'removalPersonalInformationReturn' => ["error" => true],
                    'removalPersonalInformationExpects' => 1,
                    'removalPersonalInformationMethod' => 'removePersonalInformationObject'
                ]
            ],
            'test array with removal personal information' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => ["error" => true, "BillingFirstnames" => "Kevin"],
                    'message_p' => json_encode(["error" => true, "BillingFirstnames" => "XXXXXXXXX"], JSON_PRETTY_PRINT),
                    'context'   => [],
                    'removalPersonalInformationReturn' => ["error" => true, "BillingFirstnames" => "XXXXXXXXX"],
                    'removalPersonalInformationExpects' => 1,
                    'removalPersonalInformationMethod' => 'removePersonalInformation'
                ]
            ],
            'test array with removal personal information empty' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => ["error" => true, "BillingFirstnames" => ""],
                    'message_p' => json_encode(["error" => true, "BillingFirstnames" => ""], JSON_PRETTY_PRINT),
                    'context'   => [],
                    'removalPersonalInformationReturn' => ["error" => true, "BillingFirstnames" => ""],
                    'removalPersonalInformationExpects' => 1,
                    'removalPersonalInformationMethod' => 'removePersonalInformation'
                ]
            ]
        ];
    }

    public function testLogException()
    {
        $exceptionMock = $this
            ->getMockBuilder(\Exception::class)
            ->setMethods(['getMessage','getTraceAsString'])
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock = $this
            ->getMockBuilder(Logger\Logger::class)
            ->setMethods(['addRecord'])
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock
            ->expects($this->once())
            ->method('addRecord')
            ->willReturn(true);

        $this->assertTrue($loggerMock->logException($exceptionMock, ['MyClass\\Response', 125]));
    }

    public function testInvalidMessage()
    {
        $obj = new \stdClass();
        $obj->resource = opendir('./'); // @codingStandardsIgnoreLine

        $requestHandlerMock = $this
            ->getMockBuilder(Logger\Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exceptionHandlerMock = $this
            ->getMockBuilder(Logger\Exception::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cronHandlerMock = $this
            ->getMockBuilder(Logger\Cron::class)
            ->disableOriginalConstructor()
            ->getMock();
        $debugHandlerMock = $this
            ->getMockBuilder(Logger\Debug::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handlers = [
            $requestHandlerMock,
            $cronHandlerMock,
            $exceptionHandlerMock,
            $debugHandlerMock
        ];
        $name = "SagePaySuiteLogger";

        $configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock
            ->expects($this->once())
            ->method('removePersonalInformationObject')
            ->with($obj)
            ->willReturn($obj);

        $loggerMock = $this
            ->getMockBuilder(Logger\Logger::class)
            ->setMethods(['addRecord'])
            ->setConstructorArgs(
                [
                    'config'      => $configMock,
                    'suiteHelper' => $suiteHelperMock,
                    'name'        => $name,
                    'handlers'    => $handlers
                ]
            )
            ->getMock();

        $loggerMock
            ->expects($this->once())
            ->method('addRecord')
            ->with('Request', "Type is not supported\r\n", [])
            ->willReturn(true);

        $this->assertTrue($loggerMock->sageLog('Request', $obj));
    }

    /**
     * @dataProvider debugLogTestDataProvider
     */
    public function testDebugLog($data)
    {
        $requestHandlerMock = $this
            ->getMockBuilder(Logger\Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exceptionHandlerMock = $this
            ->getMockBuilder(Logger\Exception::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cronHandlerMock = $this
            ->getMockBuilder(Logger\Cron::class)
            ->disableOriginalConstructor()
            ->getMock();
        $debugHandlerMock = $this
            ->getMockBuilder(Logger\Debug::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $type = Logger\Logger::LOG_DEBUG;
        $message = "ERROR TEST";
        $context = [];
        $handlers = [
            $requestHandlerMock,
            $cronHandlerMock,
            $exceptionHandlerMock,
            $debugHandlerMock
        ];
        $name = "SagePaySuiteLogger";

        $configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock = $this
            ->getMockBuilder(Logger\Logger::class)
            ->setMethods(['sageLog'])
            ->setConstructorArgs(
                [
                    'config'      => $configMock,
                    'suiteHelper' => $suiteHelperMock,
                    'name'        => $name,
                    'handlers'    => $handlers
                ]
            )
            ->getMock();

        $configMock
            ->expects($this->once())
            ->method('getDebugMode')
            ->willReturn($data['debugModeEnable']);

        $loggerMock
            ->expects($this->exactly($data['expectsSageLog']))
            ->method('sageLog')
            ->with($type, $message, $context)
            ->willReturn(true);

        $this->assertEquals($data['expectedReturn'], $loggerMock->debugLog($message, $context));
    }

    public function debugLogTestDataProvider()
    {
        return [
            'test debug mode enabled' => [
                [
                    'debugModeEnable' => true,
                    'expectsSageLog' => 1,
                    'expectedReturn' => true
                ]
            ],
            'test debug mode disable' => [
                [
                    'debugModeEnable' => false,
                    'expectsSageLog' => 0,
                    'expectedReturn' => false
                ]
            ]
        ];
    }
}
