<?php

namespace DvsaApplicationLogger\Factory;

use DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ReplaceTraceArgsProcessorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @return ReplaceTraceArgsProcessor
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new ReplaceTraceArgsProcessor(
            $this->createDatabaseCredentialsMasking($container->get('Config'))
        );
    }

    /**
     * @param array $config
     * @return array
     */
    private function createDatabaseCredentialsMasking(array $config)
    {
        $replaceMap = [];

        if(isset($config['DvsaApplicationLogger']['maskDatabaseCredentials'])
            && isset($config["doctrine"]["connection"]["orm_default"]["params"])
        ) {
            $maskCredentialsConfig = $config['DvsaApplicationLogger']['maskDatabaseCredentials'];
            $mask = isset($maskCredentialsConfig['mask']) ? $maskCredentialsConfig['mask'] : null;
            $argsToMask = isset($maskCredentialsConfig['argsToMask']) ? $maskCredentialsConfig['argsToMask'] : null;

            $doctrineConnectionParams = $config["doctrine"]["connection"]["orm_default"]["params"];

            if(!empty($argsToMask)) {
                foreach($argsToMask as $arg) {
                    $replaceMap[$doctrineConnectionParams[$arg]] = $mask;
                }
            }
        }

        return $replaceMap;
    }
}
