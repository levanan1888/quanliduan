<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Project Management API',
            ],
            'routes' => [
                'api' => 'api/documentation',
            ],
            'paths' => [
                'use_absolute_path' => false,
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => 'json',
                'base' => env('L5_SWAGGER_BASE_PATH', null),
                'annotations' => [
                    base_path('app/Http'),
                    base_path('app/Http/Controllers'),
                    base_path('app/Http/Schemas'),
                ],
            ],
            'constants' => [
                'L5_SWAGGER_CONST_HOST' => env('APP_URL', 'http://localhost:8080'),
            ],
        ],
    ],
    'securityDefinitions' => [
        'sanctum' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
    ],
];


