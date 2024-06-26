<?php

namespace DvsaApplicationLogger\Formatter;

use Exception;

/**
 * A JSON formatter for DVSA Logger.
 */
class Json extends General
{
    /**
     * @var array
     */
    protected $output = [
        'microtimeTimestamp',
        'timestamp',
        'priority',
        'priorityName',
        'level',
        'logEntryType',
        'username',
        'token',
        'traceId',
        'parentSpanId',
        'spanId',
        'callerName',
        'logger_name',
        'exceptionType',
        'errorCode',
        'message',
        'extra',
        'stacktrace',
    ];

    /**
     * Format the event as a JSON string and return it.
     *
     * @param array $event containing event data.
     *
     * @return string
     *
     * @throws Exception
     */
    public function format($event)
    {
        $data = $this->getEventData($event);
        $encoded = json_encode($data);

        if ($encoded === false) {
            throw new Exception("Failed to format event as JSON string.");
        }

        return $encoded;
    }
}
