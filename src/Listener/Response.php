<?php

namespace DvsaApplicationLogger\Listener;

use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Log\Writer\WriterInterface;
use Laminas\Log\Logger as Log;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\PhpEnvironment\Request as PhpRequest;
use Laminas\Http\PhpEnvironment\Response as PhpResponse;

/**
 * Class Request
 *
 * @package Application\Event
 */
class Response implements ListenerAggregateInterface
{
    /**
     * @var Log
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $log;

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * @param Log $log
     */
    public function __construct(Log $log = null)
    {
        if (!is_null($log)) {
            $this->setLog($log);
        }
    }

    /**
     * @return Log
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function setLog(Log $log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param callable $listener
     *
     * @return $this
     */
    public function addListener(callable $listener)
    {
        $this->listeners[] = $listener;

        return $this;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    public function removeListener($index)
    {
        if (!empty($this->listeners[$index])) {
            unset($this->listeners[$index]);

            return true;
        }

        return false;
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     *
     * @todo is this method redundant now the listeners are attached in Module.php?
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->addListener($events->attach(MvcEvent::EVENT_FINISH, array($this, 'logResponse')));
        $this->addListener($events->attach(MvcEvent::EVENT_FINISH, array($this, 'shutdown'), -1000));
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->getListeners() as $index => $listener) {
            $events->detach($listener);
            $this->removeListener($index);
        }
    }

    /**
     * @param MvcEvent $event
     */
    public function logResponse(MvcEvent $event): void
    {
        $request = $event->getRequest();
        if ($request instanceof PhpRequest) {
            /** @var PhpResponse */
            $response = $event->getResponse();
            /** @var string */
            $host = $request->getUri()->getHost();

            $this->getLog()->debug(
                print_r(
                    array(
                         $host => array(
                            'Response' => array(
                                'statusCode' => $response->getStatusCode(),
                                'content'    => $response->getContent()
                            )
                        )
                    ),
                    true
                )
            );
        }
    }

    public function shutdown(): void
    {
        /** @var WriterInterface */
        foreach ($this->getLog()->getWriters() as $writer) {
            $writer->shutdown();
        }
    }
}
