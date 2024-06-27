<?php

namespace DvsaApplicationLogger\Factory;

use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ReplaceTraceArgsProcessorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string|null $name
     *
     * @return ReplaceTraceArgsProcessor
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        /** @var array */
        $config = $container->get('Config');

        return new ReplaceTraceArgsProcessor(
            $this->createDatabaseCredentialsMasking($config)
        );
    }

    /**
     * @param array $config
     * @return array
     */
    private function createDatabaseCredentialsMasking(array $config)
    {
        $replaceMap = [];

        if (
            isset($config['DvsaApplicationLogger']['maskDatabaseCredentials'])
            && isset($config["doctrine"]["connection"]["orm_default"]["params"])
        ) {
            $maskCredentialsConfig = $config['DvsaApplicationLogger']['maskDatabaseCredentials'];
            $mask = isset($maskCredentialsConfig['mask']) ? $maskCredentialsConfig['mask'] : null;
            $argsToMask = isset($maskCredentialsConfig['argsToMask']) ? $maskCredentialsConfig['argsToMask'] : null;

            $doctrineConnectionParams = $config["doctrine"]["connection"]["orm_default"]["params"];

            if (!is_null($argsToMask) && !empty($argsToMask)) {
                foreach ($argsToMask as $arg) {
                    $replaceMap[$doctrineConnectionParams[$arg]] = $mask;
                }
            }
        }

        return $replaceMap;
    }
}
