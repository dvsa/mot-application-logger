<?php

namespace DvsaApplicationLogger\Log;

/**
 * This is a console logger, used to output info to sdtout
 *
 * @package DvsaApplicationLogger\Log
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsoleLogger extends Logger
{
    protected function getUuid()
    {
        return "";
    }
}
