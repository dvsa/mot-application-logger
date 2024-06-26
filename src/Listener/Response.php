<?php

namespace DvsaApplicationLogger\Listener;

use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Log\Writer\WriterInterface;
use Laminas\Log\Logger as Log;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\PhpEnvironment\Request;
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
        if ($event->getRequest() instanceof \Laminas\Http\PhpEnvironment\Request) {
            /** @var Request */
            $request = $event->getRequest();
            /** @var PhpResponse */
            $response = $event->getResponse();

            $this->getLog()->debug(
                print_r(
                    array(
                        $request->getUri()->getHost() => array(
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
