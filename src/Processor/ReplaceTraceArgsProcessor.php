<?php

namespace DvsaApplicationLogger\Processor;

use Laminas\Log\Logger;
use Laminas\Log\Processor\ProcessorInterface;

class ReplaceTraceArgsProcessor implements ProcessorInterface
{
    /**
     * @var array
     */
    private $replaceFrom;
    /**
     * @var array
     */
    private $replaceTo;

    public function __construct(array $replaceMap)
    {
        $this->replaceFrom = array_keys($replaceMap);
        $this->replaceTo = array_values($replaceMap);
    }

    /**
     * Processes a log message before it is given to the writers
     *
     * @param  array $event
     * @return array
     */
    public function process(array $event)
    {
        if ($event["priority"] <= Logger::ERR) {
            if (!empty($event["extra"]["trace"])) {
                $trace = &$event["extra"]["trace"];

                array_walk_recursive($trace, function (mixed &$item) {
                    if (is_string($item)) {
                        $item = str_replace($this->replaceFrom, $this->replaceTo, $item);
                    }
                });
            }
        }

        return $event;
    }
}
