<?php

namespace DvsaApplicationLoggerTest\Factory;

use DvsaApplicationLogger\TokenService\TokenServiceInterface;
use DvsaApplicationLogger\Factory\LoggerFactory;
use DvsaApplicationLogger\Helper\SapiHelper;
use DvsaApplicationLogger\Log\SystemLogLogger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class LoggerFactoryTest extends TestCase
{
    public function testExceptionIsThrownIfNoConfigIsProvided(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A DvsaApplicationLogger config can not be loaded.');

        $mock = $this
            ->getMockBuilder(\Laminas\ServiceManager\ServiceManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $mock->expects($this->once())
             ->method('get')
             ->will($this->returnValue([]));

        $factory = new LoggerFactory();
        $factory($mock, null, []);
    }

    /**
     * Checks if factory creates apropriate logger service. When app is invoked from CLI, logger should output
     * everything back to the user instead of only logging it
     * @dataProvider loggerInstancesDataProvider
     * @param class-string $loggerClass class name of created service
     * @param $requestObject Request object
     * @throws \Exception
     */
    public function testLoggerCreatedInstanceOfLogger(bool $isConsoleRequest, $loggerClass): void
    {
        $systemLogLogger = $this->getMockBuilder(SystemLogLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $replaceTraceProcessor = new ReplaceTraceArgsProcessor(["asd" => "asdasd"]);

        $mockSapiHelper = $this->getMockBuilder(SapiHelper::class)->disableOriginalConstructor()->onlyMethods(['requestIsConsole'])->getMock();
        $mockSapiHelper->expects($this->atLeastOnce())
            ->method('requestIsConsole')
            ->willReturn($isConsoleRequest);
        $mockContainer = $this->getMockBuilder(ContainerInterface::class)->disableOriginalConstructor()->onlyMethods(['get', 'has'])->getMock();

        $mockContainer->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnCallback(function (mixed $arg) use ($systemLogLogger, $replaceTraceProcessor, $mockSapiHelper) {
                $map = array(
                    'Config' => ['DvsaApplicationLogger' => []],
                    SapiHelper::class => $mockSapiHelper,
                    'tokenService' => $this->getMockBuilder(TokenServiceInterface::class)->disableOriginalConstructor()->onlyMethods(['getToken'])->getMock(),
                     SystemLogLogger::class => $systemLogLogger,
                     ReplaceTraceArgsProcessor::class => $replaceTraceProcessor,
                    'MotIdentityProvider' => null
                );
                return $map[$arg];
            }));

        $factory = new LoggerFactory();
        $logger = $factory($mockContainer, null, []);

        $this->assertInstanceOf(\Laminas\Log\Logger::class, $logger);
        $this->assertInstanceOf($loggerClass, $logger);
    }

    /**
     * Returns Request object and corresponding expected logger clas
     * @return array
     */
    public function loggerInstancesDataProvider()
    {
        return [
            [
                true,
                \DvsaApplicationLogger\Log\ConsoleLogger::class,
            ],
            [
                false,
                \DvsaApplicationLogger\Log\Logger::class,
            ],
        ];
    }
}
