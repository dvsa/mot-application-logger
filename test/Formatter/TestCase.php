<?php

namespace DvsaApplicationLoggerTest\Formatter;


class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Error
     */
    protected $formatter;

    public function setUp():void
    {
        $this->formatter = new General();
    }

    protected $formatterFields = [];

    /**
     * Generates log event params for formatter and expected output. For each field from log format
     * https://wiki.i-env.net/display/EA/Logging+Formats
     * it fills only one field with sample string and generates corresponfing expected output
     * @return array
     */
    public function logFormatterInputOutputDataProvider()
    {
        $eventStructure = [
            'extra' => [
                '__dvsa_metadata__' => [],
            ]
        ];

        $dvsa_metadata = $this->formatterFields;

        $out = $this->generateExpectedFormatterInputOutput($dvsa_metadata, $eventStructure);

        return $out;
    }

    public function generateExpectedFormatterInputOutput($dvsa_metadata, $eventStructure)
    {
        $fieldCount = count($dvsa_metadata);
        $output = [];
        $i = 0;
        foreach ($dvsa_metadata as $fieldName => $fieldValue) {
            $fieldsCopy = $dvsa_metadata;
            $eventCopy = $eventStructure;
            $fieldsCopy[$fieldName] = $fieldName;
            $eventCopy['extra']['__dvsa_metadata__'] = $fieldsCopy;
            $output[] = [
                $eventCopy,
                $this->generateExpectedFormatterOutput($fieldName, $i, $fieldCount),
            ];

            $i++;
        }

        return $output;
    }

    /**
     * Based on the position and field count, repeats "||" string around testing string
     * @param string $probeString
     * @param int $probeStringPosition
     * @param int $fieldCount
     * @return string
     */
    protected function generateExpectedFormatterOutput($probeString, $probeStringPosition, $fieldCount)
    {
        $this->setUp();
        $output = $this->formatter->getLogEntryPrefix()
            . str_repeat($this->formatter->getLogFieldDelimiter(), $probeStringPosition)
            . $probeString
            . str_repeat($this->formatter->getLogFieldDelimiter(), $fieldCount - 1 - $probeStringPosition);
        return $output;
    }
}