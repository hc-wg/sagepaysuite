<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Logger;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider sageLogDataProvider
     */
    public function testSageLog($data)
    {
        $loggerMock = $this
            ->getMockBuilder(Logger\Logger::class)
            ->setMethods(['addRecord'])
            ->disableOriginalConstructor()
            ->getMock();

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
                    'context'   => ['Zarata', 34]
                ]
            ],
            'test string' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => "ERROR TEST",
                    'message_p' => "ERROR TEST",
                    'context'   => []
                ]
            ],
            'test array' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => ["error" => true],
                    'message_p' => json_encode(["error" => true], JSON_PRETTY_PRINT),
                    'context'   => []
                ]
            ],
            'test object' => [
                [
                    'type'      => Logger\Logger::LOG_REQUEST,
                    'message'   => (object)["error" => true],
                    'message_p' => json_encode(((object)["error" => true]), JSON_PRETTY_PRINT),
                    'context'   => ['MyClass\\Test', 69]
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
        $loggerMock = $this
            ->getMockBuilder(Logger\Logger::class)
            ->setMethods(['addRecord'])
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock
            ->expects($this->once())
            ->method('addRecord')
            ->with('Request', "Type is not supported\r\n", [])
            ->willReturn(true);

        $obj = new \stdClass();
        $obj->resource = opendir('./'); // @codingStandardsIgnoreLine

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
                    'config'   => $configMock,
                    'name'     => $name,
                    'handlers' => $handlers
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
