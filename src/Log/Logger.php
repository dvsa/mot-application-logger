<?php

namespace DvsaApplicationLogger\Log;

use Exception;
use Interop\Container\ContainerInterface;
use Laminas\Log\Logger as LaminasLogger;

/**
 * This is a bespoke logger class for the DVSA MOT project. It tracks
 * additional properties in relation to the request. It also tracks extra info
 * when an exception is thrown.
 *
 * @package DvsaApplicationLogger\Log
 */
class Logger extends LaminasLogger
{
    const ZEND_LOGGER_CRIT_LOG_LEVEL = 'CRIT';
    const ZEND_LOGGER_EMERG_LOG_LEVEL = 'EMERG';
    const ZEND_LOGGER_ERROR_LOG_LEVEL = 'ERR';
    const ERROR_LOG_LEVEL = 'ERROR';
    const ZEND_LOGGER_NOTICE_LOG_LEVEL = 'NOTICE';
    const INFO_LOG_LEVEL = 'INFO';
    const ZEND_LOGGER_ALERT_LOG_LEVEL = 'ALERT';
    const WARN_LOG_LEVEL = 'WARN';

    /**
     * Used to check if log method is invoked from Laminas\Log\Logger so the stacktrace is deeper
     * @var string
     */
    protected $parentCallingItsMethods = 'Laminas\Log';

    private ?string $token = null;

    /**
     * @var string
     */
    private $traceId = '';

    /**
     * @var string
     */
    private $parentSpanId = '';

    /**
     * @var string
     */
    private $spanId = '';

    /**
     * @var string
     */
    private $logEntryType = 'General';

    /**
     * @deprecated
     * @var Exception
     */
    private $exception;

    /**
     * @var SystemLogLogger
     */
    private $errorLogLogger;

    /** @var ContainerInterface */
    private $serviceLocator;

    public function __construct($options, SystemLogLogger $systemLogLogger, ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->errorLogLogger = $systemLogLogger;
        parent::__construct($options);
    }

    /**
     * @deprecated you should pass exceptions in the $extras argument of
     * logging functions, e.g.: Logger::err('Message', ['ex' => $exception])
     * @param Exception $exception
     * @codeCoverageIgnore
     */
    public function setException(Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @param string $traceId
     */
    public function setTraceId($traceId)
    {
        $this->traceId = $traceId;
    }

    /**
     * trace id used when displaying error number to user.
     * @return string
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * @return string
     */
    public function getParentSpanId() : string
    {
        return $this->parentSpanId;
    }

    /**
     * @param string $parentSpanId
     * @return Logger
     */
    public function setParentSpanId($parentSpanId)
    {
        $this->parentSpanId = $parentSpanId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSpanId() : string
    {
        return $this->spanId;
    }

    /**
     * @param string $spanId
     * @return Logger
     */
    public function setSpanId($spanId)
    {
        $this->spanId = $spanId;
        return $this;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @param string $logEntryType
     */
    public function setLogEntryType($logEntryType)
    {
        $this->logEntryType = $logEntryType;
    }

    /**
     * Log a message.
     * If you want to log exception, put it in $extra array, at key "ex". The same goes for all other logging
     * functions - err, debug, info, crit, etc.
     *
     * {@inheritDoc}
     *
     * @param int $priority
     * @param mixed $message
     * @param array $extra
     * @return $this
     */
    public function log($priority, $message, $extra = [])
    {
        $metadata = $this->getBasicMetadata($priority);

        if (isset($extra['ex']) && $extra['ex'] instanceof \Throwable) {
            $metadata += $this->getExceptionMetadata($extra['ex']);
            unset($extra['ex']);
        }

        $extra['__dvsa_metadata__'] = $metadata;

        // I think we're sending mixed messages here
        parent::log($priority, $message, $extra);
        return $this;
    }

    /**
     * Return the calling function name
     * @param null $exception if exception is null it tries to get caller from debug_backtrace()
     * @return string
     */
    protected function getCallerName($exception = null)
    {
        if (!is_null($exception) && isset($exception->getTrace()[0]))
        {
            return $this->formatTraceCaller($exception->getTrace()[0]);
        }

        $trace = debug_backtrace();
        if (isset($trace[3]) && isset($trace[3]['class']) && strpos($trace[3]['class'], $this->parentCallingItsMethods) === 0) {
            if (isset($trace[4])){
                return $this->formatTraceCaller($trace[4]);
            }
        } else if (isset($trace[2]) && isset($trace[3])) {
            return $this->formatTraceCaller($trace[3]);
        }

        return $this->reasonForNotBeingAbleToLogException($exception, $trace);
    }


    private function reasonForNotBeingAbleToLogException($exception, $trace)
    {
        $invalidValues = [];

        if (!isset($trace[3])) {
            $invalidValues[] = '$trace[3]';
        } elseif (!isset($trace[3]['class'])) {
            $invalidValues[] = '$trace[3][\'class\']';
        } elseif (strpos($trace[3]['class'], $this->parentCallingItsMethods) !== 0) {
            $invalidValues[] = 'strpos';
        }

        if (!isset($trace[4])) {
            $invalidValues[] = '$trace[4]';
        }

        if (!isset($trace[2])) {
            $invalidValues[] = '$trace[2]';
        }

        if ($exception == null) {
            $invalidValues[] = 'exception';
        } elseif(!isset($exception->getTrace()[0])) {
            $invalidValues[] = '$exception->getTrace()[0]';
        }


        return 'Application logger was not able to log exception, invalid values: '
            . join(', ', $invalidValues);

    }

    /**
     * Formats trace entry as string
     * @param array $traceEntry
     * @return string
     */
    protected function formatTraceCaller(array $traceEntry)
    {
        if (isset($traceEntry['class'])) {
            return $traceEntry['class'] . '\\' . $traceEntry['function'];
        } else {
            return $traceEntry['function'];
        }
    }

    /**
     * Generates DVSA format timestamp
     * @param string $microtime from microtime()
     * @return string e.g. "2014-12-31 14:30:05.123456 Z"
     */
    protected function getMicrosecondsTimestamp($microtime)
    {
        list($usec, $sec) = explode(" ",$microtime);
        $miliseconds = substr($usec, 2, 6);
        return date('Y-m-d H:i:s', $sec) . '.' . $miliseconds . ' Z';
    }

    /**
     * @param string $microtime from microtime()
     *
     * @return string
     */
    protected function getTimestamp($microtime)
    {
        list($usec, $sec) = explode(" ",$microtime);
        $miliseconds = substr($usec, 2, 3);
        return date("Y-m-d\TH:i:s" . "." . $miliseconds . "P", $sec);
    }

    /**
     * Return basic information about error and user
     *
     * @param $priority
     *
     * @return array
     */
    protected function getBasicMetadata($priority)
    {
        return [
            'username' => $this->getUuid(),
            'token' => $this->token,
            'traceId' => $this->traceId,
            'spanId' => $this->spanId,
            'parentSpanId' => $this->parentSpanId,
            'logEntryType' => $this->logEntryType,
            'microtimeTimestamp' => $this->getMicrosecondsTimestamp(microtime()),
            'timestamp' => $this->getTimestamp(microtime()),
            'callerName' => $this->getCallerName(),
            'logger_name' => $this->getCallerName(),
            'level' => $this->transformLogLevelForLogging($this->priorities[$priority]),
        ];
    }

    /**
     * Returns info about exception
     * @param \Throwable $exception
     * @return array
     */
    protected function getExceptionMetadata(\Throwable $exception): array
    {
        return [
            'logEntryType' => 'Exception',
            'callerName' => $this->getCallerName($exception),
            'logger_name' => $this->getCallerName($exception),
            'stacktrace' => (new FilteredStackTrace())->getTraceAsString($exception),
            'errorCode' => $exception->getCode(),
            'exceptionType' => get_class($exception),
        ];
    }

    /**
     * Returns user UUID
     * @return string
     */
    protected function getUuid()
    {
        try {
            $identity = $this->serviceLocator->get('MotIdentityProvider')->getIdentity();
            return !is_null($identity) ? $identity->getUuid() : "";
        } catch (Exception $e) {
            $this->errorLogLogger->recursivelyLogExceptionToSystemLog($e);
            return "";
        }
    }

    /**
     * @param string $level
     *
     * @return string
     */
    private function transformLogLevelForLogging($level)
    {
        /*
         * Log levels from Zend Logger are transformed to be consistent with the Java services log levels.
         */
        switch ($level) {
            case self::ZEND_LOGGER_CRIT_LOG_LEVEL:
            case self::ZEND_LOGGER_EMERG_LOG_LEVEL:
            case self::ZEND_LOGGER_ERROR_LOG_LEVEL:
                return self::ERROR_LOG_LEVEL;
            case self::ZEND_LOGGER_NOTICE_LOG_LEVEL:
                return self::INFO_LOG_LEVEL;
            case self::ZEND_LOGGER_ALERT_LOG_LEVEL:
                return self::WARN_LOG_LEVEL;
            default:
                return $level;
        }
    }
}
