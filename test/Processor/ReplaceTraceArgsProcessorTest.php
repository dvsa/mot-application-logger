<?php

use DvsaApplicationLogger\Log\Logger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;

class ReplaceTraceArgsProcessorTest extends \DvsaApplicationLoggerTest\Formatter\TestCase
{
    const PASSWORD = "secretPassword";

    /** @var  ReplaceTraceArgsProcessor */
    private $sut;

    public function setUp():void
    {
        $replacementMap = [
            self::PASSWORD => "********",
        ];

        $this->sut = new ReplaceTraceArgsProcessor($replacementMap);
    }

    public function testTraceArgsBeingReplaced()
    {
        $event = [
            "priority" => Logger::ERR,
            "extra"    => [
                "trace" => $this->buildTrace(),
            ],
        ];

        $processedEvent = $this->sut->process($event);
        $this->assertStringIsNotPresentInArray($processedEvent["extra"]["trace"]);
    }

    public function testWarningsAndBelowAreNotProcessed()
    {
        $event = [
            "priority" => Logger::WARN,
            "extra"    => [
                "trace" => $this->buildTrace(),
            ],
        ];

        $processedEvent = $this->sut->process($event);
        $this->assertStringIsPresentInArray($processedEvent["extra"]["trace"]);
    }

    private function buildTrace()
    {
        return [
            [
                "args" => [
                    "meaningless",
                    "nothingImportant",
                    self::PASSWORD,
                    [
                        "secretPassword",
                        "normalText"
                    ]
                ]
            ],
            [
                "args" => [
                    "meaningless",
                    "nothingImportant",
                    self::PASSWORD,
                ],
            ],
        ];
    }

    private function assertStringIsNotPresentInArray($trace)
    {
        $count = 0;
        array_walk_recursive($trace, function($item) use (&$count) {
            if($item == self::PASSWORD) {
                $count++;
            }
        });

        $this->assertEquals(0, $count);
    }

    private function assertStringIsPresentInArray($trace)
    {
        $count = 0;
        array_walk_recursive($trace, function($item) use (&$count) {
            if($item == self::PASSWORD) {
                $count ++;
            }
        });

        $this->assertGreaterThan(0, $count);
    }
}