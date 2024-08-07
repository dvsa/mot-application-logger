<?php

return [
    'DvsaApplicationLogger' => [
        'registerExceptionHandler' => true,
        'writers' => [
            'api-flat-file' => [
                'adapter' => '\Laminas\Log\Writer\Stream',
                'options' => [
                    'output' => '/var/log/dvsa/mot-api.log',
                ],
                'filter' => \Laminas\Log\Logger::ERR,
                'enabled' => false
            ],
            'web-frontend-flat-file' => [
                'adapter' => '\Laminas\Log\Writer\Stream',
                'options' => [
                    'output' => '/var/log/dvsa/mot-webfrontend.log',
                ],
                'filter' => \Laminas\Log\Logger::ERR,
                'enabled' => false
            ],
            'api-json-file' => [
                'adapter' => '\Laminas\Log\Writer\Stream',
                'options' => [
                    'output' => '/var/log/dvsa/mot-api.json',
                    'formatter' => [
                        'name' => '\DvsaApplicationLogger\Formatter\Json'
                    ]
                ],
                'filter' => \Laminas\Log\Logger::ERR,
                'enabled' => false
            ],
            'web-frontend-json-file' => [
                'adapter' => '\Laminas\Log\Writer\Stream',
                'options' => [
                    'output' => '/var/log/dvsa/mot-webfrontend.json',
                    'formatter' => [
                        'name' => '\DvsaApplicationLogger\Formatter\Json'
                    ]
                ],
                'filter' => \Laminas\Log\Logger::ERR,
                'enabled' => false
            ],
        ],
        'maskDatabaseCredentials2' => [
            'mask' => '********',
            'argsToMask' => [ 'password', ],
        ]
    ]
];
