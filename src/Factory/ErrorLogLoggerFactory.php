<?php

namespace DvsaApplicationLogger\Factory;

use DvsaApplicationLogger\Log\SystemLogLogger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaApplicationLogger\Writer\ErrorLogWriter;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ErrorLogLoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, array $args = null)
    {
        return new SystemLogLogger(new ErrorLogWriter(), $container->get(ReplaceTraceArgsProcessor::class));
    }
}
