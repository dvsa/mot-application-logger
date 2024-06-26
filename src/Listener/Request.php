<?php

namespace DvsaApplicationLogger\Listener;

use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventInterface;
use Laminas\Log\Logger as Log;
use Laminas\Mvc\MvcEvent;

/**
 * Class Request
 *
 * @package Application\Event
 */
class Request implements ListenerAggregateInterface
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
     * @return Request
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
        $this->addListener($events->attach(MvcEvent::EVENT_ROUTE, array($this, 'logRequest')));
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
    public function logRequest(MvcEvent $event): void
    {
        if ($event->getRequest() instanceof \Laminas\Http\PhpEnvironment\Request) {
            $this->getLog()->debug(
                print_r(
                    array(
                        $event->getRequest()->getUri()->getHost() => array(
                            'Request' => $event->getRequest()->getUri()
                        )
                    ),
                    true
                )
            );
        }
    }
}
