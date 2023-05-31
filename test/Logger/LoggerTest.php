<?php

namespace DvsaApplicationLoggerTest\Logger;

use AccountApi\Service\TokenService;
use DvsaApplicationLogger\Factory\LoggerFactory;
use DvsaApplicationLogger\Helper\SapiHelper;
use DvsaApplicationLogger\Log\Logger;
use DvsaApplicationLogger\Log\SystemLogLogger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaApplicationLogger\Log\FilteredStackTrace;

use DvsaCommon\Auth\MotIdentity;
use DvsaCommon\Auth\MotIdentityProvider;
use Laminas\Stdlib\SplPriorityQueue;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    const LOGGER_TIMESTAMP_REGEX = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])T[0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}/';

    /**
     * @dataProvider getLogger
     * @param Logger $logger
     */
    public function testProperErrorExceptionLogging($logger)
    {
        $message = 'logger test message';
        $code = 400;
        $exception = new \Exception($message, $code);

        $logger->debug($message, ['ex' => $exception]);
        $logger->log($logger::CRIT, $message, ['ex' => $exception]);
        $logger->log($logger::NOTICE, $message);
        $logger->log($logger::ALERT, $message);
        $writer = $logger->getWriters()->toArray()[0];

        $this->assertEquals(Logger::ERROR_LOG_LEVEL, $writer->events[1]['extra']['__dvsa_metadata__']['level']);
        $this->assertEquals(Logger::INFO_LOG_LEVEL, $writer->events[2]['extra']['__dvsa_metadata__']['level']);
        $this->assertEquals(Logger::WARN_LOG_LEVEL, $writer->events[3]['extra']['__dvsa_metadata__']['level']);

        $this->assertBasicMetadataArePresent($writer->events[0]);
        $this->assertBasicMetadataArePresent($writer->events[1]);
        $this->assertBasicMetadataArePresent($writer->events[2]);
        $this->assertBasicMetadataArePresent($writer->events[3]);

        $this->assertExceptionMetadataArePresent($writer->events[0]);
        $this->assertExceptionMetadataArePresent($writer->events[1]);

        $this->assertCallerName($writer->events[0], __CLASS__ . '\\' . __FUNCTION__);
        $this->assertCallerName($writer->events[1], __CLASS__ . '\\' . __FUNCTION__);
        $this->assertCallerName($writer->events[2], __CLASS__ . '\\' . __FUNCTION__);
        $this->assertCallerName($writer->events[3], __CLASS__ . '\\' . __FUNCTION__);

        $this->assertStacktrace($writer->events[0], (new FilteredStackTrace())->getTraceAsString($exception));
        $this->assertStacktrace($writer->events[1], (new FilteredStackTrace())->getTraceAsString($exception));

        $this->assertExceptionCode($writer->events[0], $code);
        $this->assertExceptionCode($writer->events[1], $code);

        $this->assertExceptionName($writer->events[0], get_class($exception));
        $this->assertExceptionName($writer->events[1], get_class($exception));
    }

    /**
     * @dataProvider getLoggerWithBrokenUuid
     * @param Logger $logger
     */
    public function testFallbackErrorLoggerLogsToErrorLog($logger)
    {
        $message = 'logger test message';
        $code = 400;
        $exception = new \Exception($message, $code);

        $logger->debug($message, ['ex' => $exception]);

        $writer = $logger->getWriters()->toArray()[0];
        $this->assertBasicMetadataArePresent($writer->events[0]);
    }

    public function getLogger()
    {

        $httpMock = $this->createMock(ContainerInterface::class);
        $systemLogLogger = $this->getMockBuilder(SystemLogLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $replaceTraceProcessor = new ReplaceTraceArgsProcessor(["asd" => "asdasd"]);

        $mockMotIdentity = $this->getMockBuilder(MotIdentity::class)->disableOriginalConstructor()->setMethods(['getUuid'])->getMock();
        $mockMotIdentity->expects($this->atLeastOnce())
            ->method('getUuid')
            ->willReturn("");

        $mockIdentityProvider = $this->getMockBuilder(MotIdentityProvider::class)->disableOriginalConstructor()->setMethods(['getIdentity'])->getMock();
        $mockIdentityProvider->expects($this->atLeastOnce())
            ->method('getIdentity')
            ->willReturn($mockMotIdentity);

        $mockSapiHelper = $this->getMockBuilder(SapiHelper::class)->disableOriginalConstructor()->setMethods(['requestIsConsole'])->getMock();
        $mockSapiHelper
            ->method('requestIsConsole')
            ->willReturnOnConsecutiveCalls(true, false);

        $consoleMock = $this->createMock(ContainerInterface::class);
        $consoleMock->expects($this->any())
             ->method('get')
             ->will($this->returnCallback(function ($arg) use ($mockSapiHelper, $systemLogLogger, $replaceTraceProcessor) {
                 $map = [
                     'Config' => ['DvsaApplicationLogger' => []],
                     'MotIdentityProvider' => null,
                     SystemLogLogger::class => $systemLogLogger,
                     ReplaceTraceArgsProcessor::class => $replaceTraceProcessor,
                     SapiHelper::class => $mockSapiHelper
                 ];
                 return $map[$arg];
             }));
        $httpMock->expects($this->any())
             ->method('get')
             ->will($this->returnCallback(function ($arg) use ($mockSapiHelper, $systemLogLogger, $replaceTraceProcessor, $mockIdentityProvider) {
                 $map = array(
                     'Config' => ['DvsaApplicationLogger' => []],
                     'tokenService' => $this->getMockBuilder(TokenService::class)->disableOriginalConstructor()->setMethods(['getToken'])->getMock(),
                     SystemLogLogger::class => $systemLogLogger,
                     ReplaceTraceArgsProcessor::class => $replaceTraceProcessor,
                     'MotIdentityProvider' => $mockIdentityProvider,
                     SapiHelper::class => $mockSapiHelper
                 );
                 return $map[$arg];
             }));

        $factory = new LoggerFactory();
        /** @var \DvsaApplicationLogger\Log\Logger $loggerConsole */
        $loggerConsole = $factory($consoleMock, null, []);
        $loggerHttp = $factory($httpMock, null, []);

        $writers = new SplPriorityQueue();
        $writers->insert(new \Laminas\Log\Writer\Mock(), 0);
        $loggerConsole->setWriters($writers);

        $writers = new SplPriorityQueue();
        $writers->insert(new \Laminas\Log\Writer\Mock(), 0);
        $loggerHttp->setWriters($writers);

        return [
            [$loggerConsole],
            [$loggerHttp],
        ];
    }

    public function getLoggerWithBrokenUuid()
    {
        $isConsoleRequest = false;

        $httpMock = $this->createMock(\Laminas\ServiceManager\ServiceManager::class);
        $systemLogLogger = $this->getMockBuilder(SystemLogLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $systemLogLogger->expects($this->once())->method("recursivelyLogExceptionToSystemLog");

        $replaceTraceProcessor = new ReplaceTraceArgsProcessor(["asd" => "asdasd"]);

        $mockSapiHelper = $this->getMockBuilder(SapiHelper::class)->disableOriginalConstructor()->setMethods(['requestIsConsole'])->getMock();
        $mockSapiHelper->expects($this->atLeastOnce())
            ->method('requestIsConsole')
            ->willReturn($isConsoleRequest);

        $mockMotIdentity = $this->getMockBuilder(MotIdentity::class)->disableOriginalConstructor()->setMethods(['getUuid'])->getMock();
        $mockMotIdentity->expects($this->atLeastOnce())
            ->method('getUuid')
            ->willReturn("");

        $mockIdentityProvider = $this->getMockBuilder(MotIdentityProvider::class)->disableOriginalConstructor()->setMethods(['getIdentity'])->getMock();
        $mockIdentityProvider->expects($this->atLeastOnce())
            ->method('getIdentity')
            ->willReturn($mockMotIdentity);

        $httpMock->expects($this->any())
             ->method('get')
             ->will($this->returnCallback(function ($arg) use ($systemLogLogger, $replaceTraceProcessor, $mockSapiHelper, $mockIdentityProvider) {
                 $map = array(
                     'Config' => ['DvsaApplicationLogger' => []],
                     'tokenService' => $this->getMockBuilder(TokenService::class)->disableOriginalConstructor()->setMethods(['getToken'])->getMock(),
                     SapiHelper::class => $mockSapiHelper,
                     SystemLogLogger::class => $systemLogLogger,
                     ReplaceTraceArgsProcessor::class => $replaceTraceProcessor,
                     'MotIdentityProvider' => $mockIdentityProvider,
                 );
                 return $map[$arg];
             }));

        $factory = new LoggerFactory();
        $loggerHttp = $factory($httpMock, null, []);

        $writers = new SplPriorityQueue();
        $writers->insert(new \Laminas\Log\Writer\Mock(), 0);
        $loggerHttp->setWriters($writers);

        return [
            [$loggerHttp],
        ];
    }

    protected function assertCallerName($event, $callerName)
    {
        $this->assertEquals($callerName, $event['extra']['__dvsa_metadata__']['callerName']);
    }

    protected function assertStacktrace($event, $trace)
    {
        $this->assertEquals($trace, $event['extra']['__dvsa_metadata__']['stacktrace']);
    }

    protected function assertExceptionCode($event, $code)
    {
        $this->assertEquals($code, $event['extra']['__dvsa_metadata__']['errorCode']);
    }

    protected function assertExceptionName($event, $classname)
    {
        $this->assertEquals($classname, $event['extra']['__dvsa_metadata__']['exceptionType']);
    }

    protected function assertBasicMetadataArePresent($event)
    {
        $this->assertArrayHasKey('priority', $event);
        $this->assertArrayHasKey('priorityName', $event);
        $this->assertArrayHasKey('message', $event);
        $this->assertArrayHasKey('extra', $event);
        $this->assertArrayHasKey('__dvsa_metadata__', $event['extra']);

        $event = $event['extra']['__dvsa_metadata__'];
        $this->assertArrayHasKey('username', $event);
        $this->assertArrayHasKey('token', $event);
        $this->assertArrayHasKey('traceId', $event);
        $this->assertArrayHasKey('parentSpanId', $event);
        $this->assertArrayHasKey('spanId', $event);
        $this->assertArrayHasKey('logEntryType', $event);
        $this->assertArrayHasKey('microtimeTimestamp', $event);
        $this->assertArrayHasKey('timestamp', $event);
        $this->assertEquals(1, preg_match(self::LOGGER_TIMESTAMP_REGEX, $event['timestamp']));
        $this->assertArrayHasKey('callerName', $event);
        $this->assertArrayHasKey('logger_name', $event);
        $this->assertArrayHasKey('level', $event);
    }

    protected function assertExceptionMetadataArePresent($event)
    {
        $event = $event['extra']['__dvsa_metadata__'];
        $this->assertArrayHasKey('stacktrace', $event);
        $this->assertArrayHasKey('errorCode', $event);
        $this->assertArrayHasKey('exceptionType', $event);
    }
}