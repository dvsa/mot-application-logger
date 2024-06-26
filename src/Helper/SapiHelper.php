<?php

namespace DvsaApplicationLogger\Helper;

class SapiHelper
{
    public function requestIsConsole(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
