<?php

namespace DvsaApplicationLogger\Formatter;

use Laminas\Log\Formatter\Base;

/**
 * A formatting class for general log messages.
 *
 * @package DvsaCommon\Log\Formatter
 */
class General extends Base
{
    protected string $logFieldDelimiter = '||';
    protected string $logEntryPrefix = '^^*';

    /**
     * @var array
     */
    protected $output = [
        'microtimeTimestamp',
        'priority',
        'priorityName',
        'logEntryType',
        'username',
        'token',
        'traceId',
        'parentSpanId',
        'spanId',
        'callerName',
        'message',
        'extra',
    ];

    /**
     * @return string
     */
    public function getLogFieldDelimiter()
    {
        return $this->logFieldDelimiter;
    }

    /**
     * @param string $logFieldDelimiter
     * @return General
     */
    public function setLogFieldDelimiter($logFieldDelimiter)
    {
        $this->logFieldDelimiter = $logFieldDelimiter;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogEntryPrefix()
    {
        return $this->logEntryPrefix;
    }

    /**
     * @param string $logEntryPrefix
     * @return General
     */
    public function setLogEntryPrefix($logEntryPrefix)
    {
        $this->logEntryPrefix = $logEntryPrefix;
        return $this;
    }

    /**
     * Format the event into a message string.
     *
     * @param array $event
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return string
     */
    public function format($event) // @phpstan-ignore-line
    {
        $data = $this->getEventData($event);

        return $this->logEntryPrefix . implode($this->logFieldDelimiter, $data);
    }

    /**
     * DVSA specific data is contained within the $extra parameter of an event,
     * using the __dvsa_metadata__ array key. This method will flatten that
     * array so that we can iterate over with a single for loop.
     *
     * @param array $event and array containing event data.
     * @return array
     */
    protected function flattenEventData(array $event)
    {
        $data = [];

        if (
            array_key_exists('extra', $event)
            && array_key_exists('__dvsa_metadata__', $event['extra'])
        ) {
            foreach ($event['extra']['__dvsa_metadata__'] as $key => $value) {
                $data[$key] = $value;
            }
            unset($event['extra']['__dvsa_metadata__']);
            if (empty($event['extra'])) {
                unset($event['extra']);
            }
        }

        return array_merge($event, $data);
    }

    /**
     * Get data using output labels.
     *
     * @param array $event array containing event data.
     * @return array
     */
    protected function getEventData(array $event)
    {
        $data = $this->flattenEventData($event);

        $out = [];
        foreach ($this->output as $name) {
            if (isset($data[$name])) {
                $out[$name] = $this->normalize($data[$name]);
            } else {
                $out[$name] = '';
            }
        }

        return $out;
    }
}
