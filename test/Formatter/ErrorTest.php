<?php

namespace DvsaApplicationLoggerTest\Formatter;

use DvsaApplicationLogger\Formatter\Error;
use DvsaApplicationLoggerTest\Formatter;

class ErrorTest extends TestCase
{
    /**
     * @var Error
     */
    protected $formatter;

    protected array $formatterFields = [
        'microtimeTimestamp' => '',
        'priority' => '',
        'priorityName' => '',
        'logEntryType' => '',
        'username' => '',
        'token' => '',
        'traceId' => '',
        'parentSpanId' => '',
        'spanId' => '',
        'callerName' => '',
        'exceptionType' => '',
        'errorCode' => '',
        'message' => '',
        'extra' => '',
        'stacktrace' => '',
    ];

    public function setUp(): void
    {
        $this->formatter = new Error();
    }

    /**
     * Tests if log formatter inserts data in correct order and does not leave placeholders
     * @dataProvider logFormatterInputOutputDataProvider
     * @param array $event
     * @param string $expectedOutput
     */
    public function testOutputFormat($event, $expectedOutput): void
    {
        $this->assertEquals($expectedOutput, $this->formatter->format($event));
    }

    /**
     * Test that the output format matches the requirements on
     * https://wiki.i-env.net/display/EA/Logging+Formats
     */
    public function testOutputFormatContainsRelevantProperties(): void
    {
        $expectedPriority = 7;
        $expectedPriorityName = 'DEBUG';
        $expectedLogEntryType = 'Foo';
        $expectedUsername = '1234';
        $expectedToken = 'token';
        $expectedMessage = 'This is a test message';
        $errorLocation = 'class-1234';
        $expectedExceptionType = 'Exception';
        $expectedExceptionCode = 123;
        $expectedTraceId = uniqid();
        $expectedParentSpanId = uniqid();
        $expectedSpanId = uniqid();
        $expectedStackTrace = 'trace';
        $expectedExtra = ['foo' => 'bar'];

        $event = [
            'priority' => $expectedPriority,
            'priorityName' => $expectedPriorityName,
            'message' => $expectedMessage,
            'extra' => [
                'foo' => 'bar',
                '__dvsa_metadata__' => [
                    'callerName' => $errorLocation,
                    'logEntryType' => $expectedLogEntryType,
                    'username' => $expectedUsername,
                    'token' => $expectedToken,
                    'errorCode' => $expectedExceptionCode,
                    'exceptionType' => $expectedExceptionType,
                    'stacktrace' => $expectedStackTrace,
                    'traceId' => $expectedTraceId,
                    'parentSpanId' => $expectedParentSpanId,
                    'spanId' => $expectedSpanId,
                ]
            ]
        ];

        $expectedString = vsprintf(
            '%s||%s||%s||%s||%s||%s||%s||%s||%s||%s||%s||%s',
            [
                $expectedLogEntryType,
                $expectedUsername,
                $expectedToken,
                $expectedTraceId,
                $expectedParentSpanId,
                $expectedSpanId,
                $errorLocation,
                $expectedExceptionType,
                $expectedExceptionCode,
                $expectedMessage,
                json_encode($expectedExtra),
                $expectedStackTrace
            ]
        );

        /** @var string */
        $actual = $this->formatter->format($event);
        $this->assertStringEndsWith($expectedString, $actual);
    }
}
