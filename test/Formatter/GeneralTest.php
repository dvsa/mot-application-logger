<?php

namespace DvsaApplicationLoggerTest\Formatter;

use DvsaApplicationLogger\Factory\LoggerFactory;
use DvsaApplicationLogger\Formatter\General;
use DvsaApplicationLogger\Log\Logger;
use Laminas\Stdlib\SplPriorityQueue;

class GeneralTest extends TestCase
{
    protected $formatterFields = [
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
        'message' => '',
        'extra' => '',
    ];

    /**
     * @var General
     */
    protected $formatter;

    public function setUp():void
    {
        $this->formatter = new General();
    }

    /**
     * Tests if log formatter inserts data in correct order and does not leave placeholders
     * @dataProvider logFormatterInputOutputDataProvider
     * @param array $event
     * @param string $expectedOutput
     */
    public function testOutputFormat($event, $expectedOutput)
    {
        $this->assertEquals($expectedOutput, $this->formatter->format($event));
    }

    /**
     * Extra data should be encoded to a json string
     */
    public function testExtraGetsEncodedToJson()
    {
        $extraData = [
            'foo' => 'bar',
            'encode' => true,
            '__dvsa_metadata__' => [
                'token' => 'bar',
                'username' => 12345,
            ]
        ];

        $expectedString = json_encode(['foo' => 'bar', 'encode' => true]);
        $output = $this->formatter->format(['extra' => $extraData]);

        $this->assertStringEndsWith($expectedString, $output);
    }

    /**
     * This will test the log message in its full format. See
     * https://wiki.i-env.net/display/EA/Logging+Formats for requirements
     */
    public function testExpectedLogMessageInFullFormat()
    {
        $expectedPriority = 7;
        $expectedPriorityName = 'DEBUG';
        $expectedLogEntryType = 'Foo';
        $expectedUsername = '1234';
        $expectedToken = 'token';
        $expectedClass = __CLASS__;
        $expectedTraceId = uniqid();
        $expectedParentSpanId = uniqid();
        $expectedSpanId = uniqid();
        $expectedMessage = 'This is a test message';
        $expectedExtra = ['foo' => 'bar'];

        $event = [
            'priority' => $expectedPriority,
            'priorityName' => $expectedPriorityName,
            'message' => $expectedMessage,
            'extra' => [
                'foo' => 'bar',
                '__dvsa_metadata__' => [
                    'logEntryType' => $expectedLogEntryType,
                    'username' => $expectedUsername,
                    'token' => $expectedToken,
                    'callerName' => $expectedClass,
                    'traceId' => $expectedTraceId,
                    'parentSpanId' => $expectedParentSpanId,
                    'spanId' => $expectedSpanId,
                ]
            ]
        ];

        // omit timestamp as we can't be sure of microsecond value
        $expectedString = vsprintf(
            '%s||%s||%s||%s||%s||%s||%s||%s||%s||%s||%s',
            [
                $expectedPriority,
                $expectedPriorityName,
                $expectedLogEntryType,
                $expectedUsername,
                $expectedToken,
                $expectedTraceId,
                $expectedParentSpanId,
                $expectedSpanId,
                $expectedClass,
                $expectedMessage,
                json_encode($expectedExtra),
            ]
        );

        $this->assertStringEndsWith($expectedString, $this->formatter->format($event));
    }

    private static function flattenTime($time) {
        $flatTime = '';
        foreach ($time as $key => $value) {
            $flatTime = $flatTime.$value;
        }
        return $flatTime;
    }
}
