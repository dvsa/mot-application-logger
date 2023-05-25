<?php
namespace DvsaApplicationLogger\Helper;


class SapiHelper {
    public function requestIsConsole() {
        return php_sapi_name() === 'cli';
    }
}

