<?php

namespace DvsaApplicationLoggerTest\Logger\Processor;

use DvsaApplicationLogger\Log\Logger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;

class ReplaceTraceArgsProcessorTest extends \DvsaApplicationLoggerTest\Formatter\TestCase
{
    private const PASSWORD = "secretPassword";

    /** @var  ReplaceTraceArgsProcessor */
    private $sut;

    public function setUp(): void
    {
        $replacementMap = [
            self::PASSWORD => "********",
        ];

        $this->sut = new ReplaceTraceArgsProcessor($replacementMap);
    }

    public function testTraceArgsBeingReplaced(): void
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

    public function testWarningsAndBelowAreNotProcessed(): void
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

    private function buildTrace(): array
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

    private function assertStringIsNotPresentInArray(array $trace): void
    {
        $count = 0;
        array_walk_recursive($trace, function (mixed $item) use (&$count) {
            if ($item == self::PASSWORD) {
                $count++;
            }
        });

        $this->assertEquals(0, $count);
    }

    private function assertStringIsPresentInArray(array $trace): void
    {
        $count = 0;
        array_walk_recursive($trace, function (mixed $item) use (&$count) {
            if ($item == self::PASSWORD) {
                $count++;
            }
        });

        $this->assertGreaterThan(0, $count);
    }
}
