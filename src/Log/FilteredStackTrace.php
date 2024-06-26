<?php

namespace DvsaApplicationLogger\Log;

class FilteredStackTrace
{
    /**
     * Arguments to hide from stack trace as regex.
     */
    const TRACE_EXCLUSIONS = "/^(password|pwd|pass|newPassword)$/";

    /**
     * Returns stack trace string similar to exception->getTraceAsString(), but with certain args blanked out
     * (see BL-5231)
     * Argument names as well as values displayed.
     * @param \Throwable $throwable
     * @return string
     */
    public function getTraceAsString(\Throwable $throwable)
    {
        $trace = $throwable->getTrace();
        $traceString = "";
        $count = 0;

        foreach ($trace as $line) {
            $traceString .= $this->getTraceLineAsFilteredString($count, $line);
            $count++;
        }
        return $traceString;
    }
    /**
     * Gets a single line of the trace.
     * @param int $count
     * @param array $line
     * @return string
     */
    protected function getTraceLineAsFilteredString($count, $line)
    {

        $currentFile = isset($line['file']) ? $line['file'] : "[internal function]";
        $currentLine = isset($line['line']) ? $line['line'] : "";
        $className = isset($line['class']) ? $line['class'] : null;
        $function = isset($line['function']) ? $line['function'] : "";
        $fullyQualifiedFunction = $className != null ? $className . "->" . $function : $function;

        $canGetArgumentNames = true;
        $argumentNames = [];
        try {
            $argumentNames = $this->getArgumentNames($function, $className);
        } catch (\Exception $e) {
            $canGetArgumentNames  = false;
        }

        $argumentsString = "";

        if (isset($line['args'])) {
            $argumentsString = $this->getArguments($line['args'], $argumentNames, $canGetArgumentNames);
        }

        return sprintf(
            "#%s %s(%s): %s(%s)\n",
            $count,
            $currentFile,
            $currentLine,
            $fullyQualifiedFunction,
            $argumentsString
        );
    }

    /**
     * From a method/function name and class, returns array of argument names
     * @param string $function
     * @param null | string $className
     * @return array
     */
    protected function getArgumentNames($function, $className)
    {
        $argumentNames = [];
        if ($className != null) {
            // Get argument names of class method (if possible).
            $ref = new \ReflectionMethod($className, $function);
            foreach ($ref->getParameters() as $param) {
                $argumentNames[] = $param->name;
            }
        } else {
            // Get argument names of function (if possible).
            $ref = new \ReflectionFunction($function);
            foreach ($ref->getParameters() as $param) {
                $argumentNames[] = $param->name;
            }
        }
        return $argumentNames;
    }

    /**
     * @param array $argumentValues
     * @param array $argumentNames
     * @param bool $canGetArgumentNames Whether the argument names could be obtained by reflection.
     * @return string
     */
    protected function getArguments($argumentValues, $argumentNames, $canGetArgumentNames)
    {
        $argumentStrings = [];
        $argumentsCount = 0;
        foreach ($argumentValues as $argumentValue) {
            if (is_string($argumentValue)) {
                if ($canGetArgumentNames && isset($argumentNames[$argumentsCount])) {
                    $value = $this->filterArgument($argumentNames[$argumentsCount], $argumentValue);
                } else {
                    // Argument name could not be determined (reflection failed), so hide values.
                    $value = "'######'";
                }
            } elseif (is_array($argumentValue)) {
                $value = "Array";
            } elseif (is_null($argumentValue)) {
                $value = 'NULL';
            } elseif (is_bool($argumentValue)) {
                $value = $argumentValue ? "true" : "false";
            } elseif (is_object($argumentValue)) {
                $value = 'Object(' . get_class($argumentValue) . ')';
            } elseif (is_resource($argumentValue)) {
                $value = get_resource_type($argumentValue);
            } else {
                $value = $argumentValue;
            }

            $argumentStrings[] = isset($argumentNames[$argumentsCount]) ? $argumentNames[$argumentsCount] . '=' . $value : $value;
            $argumentsCount++;
        }
        return join(", ", $argumentStrings);
    }

    /**
     * Filters out arguments that match out trace exclusions.
     * @param $argumentName
     * @param $value
     * @return string
     */
    protected function filterArgument($argumentName, $value)
    {
        if (preg_match(self::TRACE_EXCLUSIONS, $argumentName)) {
            return "'******'";
        }
        return "'$value'";
    }
}
