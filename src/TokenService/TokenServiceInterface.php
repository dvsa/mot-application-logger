<?php

namespace DvsaApplicationLogger\TokenService;

/**
 * Interface TokenServiceInterface
 * @package DvsaApplicationLogger
 *
 * Impl handles the obtaining of the token, but it is necessary to make the token available for the logger
 */
interface TokenServiceInterface
{
    public function getToken(): null|string;
}
