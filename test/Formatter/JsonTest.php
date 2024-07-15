<?php

namespace DvsaApplicationLoggerTest\Formatter;

use DvsaApplicationLogger\Formatter\General;
use DvsaApplicationLogger\Formatter\Error;
use DvsaApplicationLogger\Formatter\Json;

class JsonTest extends TestCase
{
    /**
     * @var General|Error|Json
     */
    protected $formatter;

    protected array $formatterFields = [
        'microtimeTimestamp',
        'timestamp',
        'priority',
        'priorityName',
        'level',
        'logEntryType',
        'username',
        'token',
        'callerName',
        'logger_name',
        'exceptionType',
        'errorCode',
        'message',
        'extra',
        'stacktrace',
    ];

    public function setUp(): void
    {
        $this->formatter = new Json();
    }

    /**
     * Test that the output format matches the requirements on
     * https://wiki.i-env.net/display/EA/Logging+Formats
     *
     * @throws \Exception
     */
    public function testOutputFormatContainsRelevantProperties(): void
    {
        $expectedPriority = 7;
        $expectedPriorityName = 'DEBUG';
        $expectedLevel = 'ERROR';
        $expectedLogEntryType = 'Foo';
        $expectedUsername = '1234';
        $expectedToken = 'token';
        $expectedMessage = 'This is a test message';
        $errorLocation = 'class-1234';
        $expectedExceptionType = 'Exception';
        $expectedExceptionCode = 123;
        $expectedStackTrace = 'trace';

        $event = [
            'priority' => $expectedPriority,
            'priorityName' => $expectedPriorityName,
            'level' => $expectedLevel,
            'message' => $expectedMessage,
            'extra' => [
                'foo' => 'bar',
                '__dvsa_metadata__' => [
                    'callerName' => $errorLocation,
                    'logger_name' => $errorLocation,
                    'logEntryType' => $expectedLogEntryType,
                    'username' => $expectedUsername,
                    'token' => $expectedToken,
                    'errorCode' => $expectedExceptionCode,
                    'exceptionType' => $expectedExceptionType,
                    'stacktrace' => $expectedStackTrace
                ]
            ]
        ];

        $expectedString = "{\"priority\":\"$expectedPriority\","
            . "\"priorityName\":\"$expectedPriorityName\","
            . "\"level\":\"$expectedLevel\","
            . "\"message\":\"$expectedMessage\","
            . "\"callerName\":\"$errorLocation\","
            . "\"logger_name\":\"$errorLocation\","
            . "\"logEntryType\":\"$expectedLogEntryType\","
            . "\"microtimeTimestamp\":\"\","
            . "\"timestamp\":\"\","
            . "\"username\":\"$expectedUsername\","
            . "\"token\":\"$expectedToken\","
            . "\"errorCode\":\"$expectedExceptionCode\","
            . "\"exceptionType\":\"$expectedExceptionType\","
            . "\"stacktrace\":\"$expectedStackTrace\","
            . "\"extra\":\"{\\\"foo\\\":\\\"bar\\\"}\"}";

        $expectedJson = json_decode($expectedString, true);
        /** @var array */
        $outputJson = json_decode($this->formatter->format($event), true);

        foreach ($this->formatterFields as $field) {
            $expectedVal = $expectedJson[$field];
            $actualVal = $outputJson[$field];
            $this->assertTrue($expectedVal == $actualVal, "Failed to match $expectedVal with $actualVal on field $field.");
        }
    }
}
