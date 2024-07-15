<?php

namespace DvsaApplicationLogger\Writer;

class ErrorLogWriter
{
    public function log(string $message, string $stackTrace): void
    {
        error_log($message . ' Stacktrace: ' . $stackTrace);
    }
}
