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
class LoggerFactory implements FactoryInterface
{
    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
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
        $config = $container->get('Config');
        if (!array_key_exists('DvsaApplicationLogger', $config)) {
            throw new \RuntimeException('A DvsaApplicationLogger config can not be loaded.');
        }

        /** @var SapiHelper $sapiHelper */
        $sapiHelper = $container->get(SapiHelper::class);

        if ($sapiHelper->requestIsConsole()) {
            $logger = $this->createConsoleRequestLogger($container);
        } else {
            $logger = $this->createHttpRequestLogger($container);
        }

        $logger::registerErrorHandler($logger);
        $logger::registerFatalErrorShutdownFunction($logger);
        $this->configuration($config['DvsaApplicationLogger']);

        $this->logger = $logger;
        return $this->logger;
    }

    /**
     * Instantiates logger if request comes from http
     * @param ContainerInterface $serviceLocator
     * @return Logger
     * @throws Exception
     */
    protected function createHttpRequestLogger(ContainerInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        $applicationLoggerConfig = $config['DvsaApplicationLogger'];
        $this->logger = new Logger([], $serviceLocator->get(SystemLogLogger::class), $serviceLocator);

        /** @var \Dvsa\Mot\Frontend\AuthenticationModule\Service\WebAuthenticationCookieService $tokenService */
        $tokenService = $serviceLocator->get('tokenService');
        $this->logger->setToken($tokenService->getToken());
        $this->logger->addProcessor($serviceLocator->get(ReplaceTraceArgsProcessor::class));
        $this->writerCollection($applicationLoggerConfig);
        $this->fallbackOnNoopWriter();
        return $this->logger;
    }

    /**
     * Instantiates logger if request comes from console
     * @param ContainerInterface $serviceLocator
     * @return ConsoleLogger
     */
    protected function createConsoleRequestLogger(ContainerInterface $serviceLocator)
    {

        $this->logger = new ConsoleLogger([], $serviceLocator->get(SystemLogLogger::class), $serviceLocator);
        $writer = new Stream('php://output');
        $this->logger->addProcessor($serviceLocator->get(ReplaceTraceArgsProcessor::class));
        $this->logger->addWriter($writer);
        return $this->logger;
    }

    /**
     * @param array $config
     * @return int
     * @throws Exception
     */
    private function writerCollection(array $config)
    {
        if (!empty($config['writers'])) {
            $writers = 0;
            foreach ($config['writers'] as $writer) {
                if ($writer['enabled']) {
                    $this->writerAdapter($writer);
                    $writers++;
                }
            }
            return $writers;
        }
    }

    /**
     * @param array $writer
     * @return AbstractWriter
     * @throws Exception
     */
    private function writerAdapter(array $writer)
    {
        /** @var  AbstractWriter $writerAdapter */
        $writerAdapter = new $writer['adapter']($writer['options']['output']);

        $this->logger->addWriter($writerAdapter);
        $writerAdapter->addFilter(
            new Priority(
                $writer['filter']
            )
        );

        if (!empty($writer['options']['formatter']) && !empty($writer['options']['formatter']['name'])) {
            $formatter = $this->getWriterFormatter($writer);
        } else {
            $formatter = new Error();
        }

        $writerAdapter->setFormatter($formatter);

        return $writerAdapter;
    }

    /**
     * @param array $config
     */
    private function configuration(array $config)
    {
        if (!empty($config['registerExceptionHandler'])) {
            $config['registerExceptionHandler'] === false ?: LaminasLogger::registerExceptionHandler($this->logger);
        }
    }

    /**
     * @return LaminasLogger
     */
    private function fallbackOnNoopWriter()
    {
        if ($this->logger->getWriters()->count() == 0) {
            return $this->logger->addWriter(new \Laminas\Log\Writer\Noop());
        }
    }

    /**
     * @param array $config
     * @return mixed
     * @throws Exception
     */
    private function getWriterFormatter(array $config)
    {
        $class = $config['options']['formatter']['name'];

        if (class_exists($class)) {
            return new $class();
        } else {
            throw new Exception("Unable to instantiate a formatter. Class $class does not exist.");
        }
    }
}
