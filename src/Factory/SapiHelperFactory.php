<?php

namespace DvsaApplicationLogger\Factory;

use DvsaApplicationLogger\Formatter\Error;
use DvsaApplicationLogger\Helper\SapiHelper;
use DvsaApplicationLogger\Log\ConsoleLogger;
use DvsaApplicationLogger\Log\SystemLogLogger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use Exception;
use Interop\Container\ContainerInterface;
use Laminas\Http\Request as LaminasHttpRequest;
use Laminas\Console\Request as ZendConsoleRequest;
use Laminas\Log\Filter\Priority;
use Laminas\Log\Logger as LaminasLogger;
use DvsaApplicationLogger\Log\Logger;
use Laminas\Log\Writer\AbstractWriter;
use Laminas\Log\Writer\Stream;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class LoggerFactory.
 * @package DvsaApplicationLogger\Factory
 */
class SapiHelperFactory implements FactoryInterface
{

    /**
     * @var SapiHelper $helper
     */
    private $helper;

    /**
     * @return mixed
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Creates a general purpose logger.
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param array $args
     * @return Logger|object
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $name, array $args = null)
    {
        $this->helper = new SapiHelper();
        return $this->helper;
    }

}
