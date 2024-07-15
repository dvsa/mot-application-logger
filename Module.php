<?php

namespace DvsaApplicationLogger;

use DvsaApplicationLogger\Factory\ErrorLogLoggerFactory;
use DvsaApplicationLogger\Factory\ReplaceTraceArgsProcessorFactory;
use DvsaApplicationLogger\Helper\SapiHelper;
use DvsaApplicationLogger\Listener\Request;
use DvsaApplicationLogger\Listener\Response;
use DvsaApplicationLogger\Log\SystemLogLogger;
use DvsaApplicationLogger\Log\Logger;
use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaApplicationLogger\TokenService\TokenServiceInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

class Module
{
    private const FULL_STACK_LOGGING_SERVICE_NAME = 'DvsaCommon\FullStackLogging\Service\FullStackLoggingService';

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $applicationLogger = $serviceManager->get('Application\Logger');
        $requestListener = new Request($applicationLogger);
        $responseListener = new Response($applicationLogger);

        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$requestListener, 'logRequest']);
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$responseListener, 'logResponse']);
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$responseListener, 'shutdown'], -1000);

        $this->attachEvents($eventManager);

        return;
    }

    protected function attachEvents($eventManager)
    {
        $processException = function (MvcEvent $e) {
            $this->processException($e);
        };

        $eventManager->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            $processException,
            PHP_INT_MAX
        );

        $eventManager->attach(
            MvcEvent::EVENT_RENDER_ERROR,
            $processException,
            PHP_INT_MAX
        );
    }

    protected function processException(MvcEvent $e)
    {
        $exception = $e->getParam('exception');
        if (!$exception) {
            return;
        }

        if (
            is_a($exception, 'DvsaCommon\Exception\UnauthorisedException') ||
            is_a($exception, 'DvsaCommon\HttpRestJson\Exception\ForbiddenOrUnauthorisedException')
        ) {
            return;
        }

        $serviceManager = $e->getApplication()->getServiceManager();

        /** @var  Logger $logger */
        $logger = $serviceManager->get('Application\Logger');
        /** @var TokenServiceInterface $tokenService */
        $tokenService = $serviceManager->get('tokenService');
        /** @var \DvsaCommon\FullStackLogging\Service\FullStackLoggingService $fullStackLoggingService */
        $fullStackLoggingService = $serviceManager->get(self::FULL_STACK_LOGGING_SERVICE_NAME);

        $logger->setToken($tokenService->getToken());
        $logger->setTraceId($fullStackLoggingService->getTraceId());
        $logger->setSpanId($fullStackLoggingService->getSpanId());
        $logger->setParentSpanId($fullStackLoggingService->getParentSpanId());

        while ($exception instanceof ServiceNotCreatedException && $exception->getPrevious()) {
            // work around Zend to log the actual problem instead of hiding it
            $exception = $exception->getPrevious();
        }

        $logger->crit($exception->getMessage(), ['ex' => $exception]);
    }

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => [
                'Application\Logger' => \DvsaApplicationLogger\Factory\LoggerFactory::class,
                SapiHelper::class => \DvsaApplicationLogger\Factory\SapiHelperFactory::class,
                ReplaceTraceArgsProcessor::class => ReplaceTraceArgsProcessorFactory::class,
                SystemLogLogger::class => ErrorLogLoggerFactory::class,
            ]
        );
    }
}
