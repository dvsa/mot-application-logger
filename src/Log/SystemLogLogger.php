<?php

namespace DvsaApplicationLogger\Log;

use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaApplicationLogger\Writer\ErrorLogWriter;
use Exception;

class SystemLogLogger
{
    /**
     * @var ErrorLogWriter
     */
    private $errorLogWriter;

    /**
     * @var ReplaceTraceArgsProcessor
     */
    private $replaceTraceArgsProcessor;

    public function __construct(
        ErrorLogWriter $errorLogWriter,
        ReplaceTraceArgsProcessor $replaceTraceArgsProcessor
    ) {
        $this->errorLogWriter = $errorLogWriter;
        $this->replaceTraceArgsProcessor = $replaceTraceArgsProcessor;
    }

    /**
     * Logs exception stack to system log using error_log()
     * @param Exception $exception
     */
    public function recursivelyLogExceptionToSystemLog($exception): void
    {
        do {
            $trace = $this->maskExceptionTrace((new FilteredStackTrace())->getTraceAsString($exception));
            $this->errorLogWriter->log($exception->getMessage(), $trace);
            $exception = $exception->getPrevious();
        } while ($exception);
    }


    /**
     * @param string $exceptionTrace
     * @return string
     */
    private function maskExceptionTrace($exceptionTrace)
    {
        $event = $this->replaceTraceArgsProcessor->process([
            "priority" => Logger::ERR,
            "extra" => [
                "trace" => [
                    0 => $exceptionTrace,
                ],
            ],
        ]);

        return $event["extra"]["trace"][0];
    }
}
