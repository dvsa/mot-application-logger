<?php


namespace DvsaApplicationLogger\Formatter;


/**
 * This class will format the log message for any exception being thrown in
 * the application.
 *
 * @package DvsaCommon\Log\Formatter
 */
class Error extends General
{
    protected $output = [
        'microtimeTimestamp',
        'priority',
        'priorityName',
        'logEntryType',
        'username',
        'token',
        'traceId',
        'parentSpanId',
        'spanId',
        'callerName',
        'exceptionType',
        'errorCode',
        'message',
        'extra',
        'stacktrace',
    ];
}
