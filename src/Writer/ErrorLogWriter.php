<?php

namespace DvsaApplicationLogger\Writer;

class ErrorLogWriter
{
    public function log($message, $stackTrace)
    {
        error_log($message . ' Stacktrace: ' . $stackTrace);
    }
}