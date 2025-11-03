<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PostmanCollectionController extends Controller
{
    /**
     * Export Postman Collection v2.1 from Swagger JSON
     */
    public function export()
    {
        $swaggerJsonPath = storage_path('api-docs/api-docs.json');
        
        if (! File::exists($swaggerJsonPath)) {
            return response()->json([
                'message' => 'Swagger documentation not generated. Run: php artisan l5-swagger:generate'
            ], 404);
        }

        $swagger = json_decode(File::get($swaggerJsonPath), true);
        
        // Convert Swagger/OpenAPI 3.0 to Postman Collection 2.1
        $collection = $this->convertToPostmanCollection($swagger);
        
        return response()->json($collection, 200, [
            'Content-Type' => 'application/json',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Download Postman Collection file
     */
    public function download()
    {
        $collection = $this->export();
        
        if ($collection->getStatusCode() !== 200) {
            return $collection;
        }
        
        $data = json_decode($collection->getContent(), true);
        $filename = 'Project-Management-API.postman_collection.json';
        
        return response()->json($data, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/json',
        ], JSON_PRETTY_PRINT);
    }

    private function convertToPostmanCollection(array $swagger): array
    {
        $baseUrl = $swagger['servers'][0]['url'] ?? 'http://localhost:8080';
        
        $collection = [
            'info' => [
                'name' => $swagger['info']['title'] ?? 'Project Management API',
                'description' => $swagger['info']['description'] ?? '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                '_exporter_id' => 'project-management-api',
            ],
            'item' => [],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => str_replace(['http://', 'https://'], '', $baseUrl),
                    'type' => 'string'
                ],
                [
                    'key' => 'access_token',
                    'value' => '',
                    'type' => 'string'
                ]
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{access_token}}',
                        'type' => 'string'
                    ]
                ]
            ]
        ];

        // Group by tags
        $itemsByTag = [];
        
        foreach ($swagger['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $tags = $operation['tags'] ?? ['Default'];
                $tagName = $tags[0];
                
                if (! isset($itemsByTag[$tagName])) {
                    $itemsByTag[$tagName] = [];
                }
                
                $item = [
                    'name' => $operation['summary'] ?? $method . ' ' . $path,
                    'request' => [
                        'method' => strtoupper($method),
                        'header' => [
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text'
                            ],
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                                'type' => 'text'
                            ]
                        ],
                        'url' => [
                            'raw' => '{{base_url}}' . $path,
                            'host' => ['{{base_url}}'],
                            'path' => array_filter(explode('/', trim($path, '/')))
                        ],
                        'auth' => isset($operation['security']) ? [
                            'type' => 'bearer',
                            'bearer' => [
                                [
                                    'key' => 'token',
                                    'value' => '{{access_token}}',
                                    'type' => 'string'
                                ]
                            ]
                        ] : null
                    ],
                    'response' => []
                ];
                
                // Add request body if exists
                if (isset($operation['requestBody']['content']['application/json']['example'])) {
                    $item['request']['body'] = [
                        'mode' => 'raw',
                        'raw' => json_encode($operation['requestBody']['content']['application/json']['example'], JSON_PRETTY_PRINT),
                        'options' => [
                            'raw' => [
                                'language' => 'json'
                            ]
                        ]
                    ];
                } elseif (isset($operation['requestBody']['content']['application/json']['schema'])) {
                    // Generate example from schema
                    $example = $this->generateExampleFromSchema($operation['requestBody']['content']['application/json']['schema'], $swagger);
                    if ($example) {
                        $item['request']['body'] = [
                            'mode' => 'raw',
                            'raw' => json_encode($example, JSON_PRETTY_PRINT),
                            'options' => [
                                'raw' => [
                                    'language' => 'json'
                                ]
                            ]
                        ];
                    }
                }
                
                // Add path parameters
                if (isset($operation['parameters'])) {
                    $urlParams = [];
                    $pathParams = [];
                    foreach ($operation['parameters'] as $param) {
                        if ($param['in'] === 'path') {
                            $pathParams[] = [
                                'key' => $param['name'],
                                'value' => $param['schema']['example'] ?? '',
                                'description' => $param['description'] ?? ''
                            ];
                        } elseif ($param['in'] === 'query') {
                            $urlParams[] = [
                                'key' => $param['name'],
                                'value' => $param['schema']['example'] ?? '',
                                'description' => $param['description'] ?? ''
                            ];
                        }
                    }
                    if (! empty($pathParams)) {
                        $item['request']['url']['variable'] = $pathParams;
                    }
                    if (! empty($urlParams)) {
                        $item['request']['url']['query'] = $urlParams;
                    }
                }
                
                $itemsByTag[$tagName][] = $item;
            }
        }
        
        // Convert to Postman collection format
        foreach ($itemsByTag as $tagName => $items) {
            $collection['item'][] = [
                'name' => $tagName,
                'item' => $items
            ];
        }
        
        return $collection;
    }

    private function generateExampleFromSchema(array $schema, array $swagger): ?array
    {
        if (isset($schema['example'])) {
            return $schema['example'];
        }
        
        if (isset($schema['$ref'])) {
            $ref = str_replace('#/components/schemas/', '', $schema['$ref']);
            if (isset($swagger['components']['schemas'][$ref])) {
                return $this->generateExampleFromSchema($swagger['components']['schemas'][$ref], $swagger);
            }
        }
        
        if (isset($schema['properties'])) {
            $example = [];
            foreach ($schema['properties'] as $key => $prop) {
                if (isset($prop['example'])) {
                    $example[$key] = $prop['example'];
                } elseif (isset($prop['type'])) {
                    switch ($prop['type']) {
                        case 'string':
                            $example[$key] = $prop['format'] === 'email' ? 'example@example.com' : 'string';
                            break;
                        case 'integer':
                            $example[$key] = 1;
                            break;
                        case 'boolean':
                            $example[$key] = true;
                            break;
                        case 'array':
                            $example[$key] = [];
                            break;
                        default:
                            $example[$key] = null;
                    }
                }
            }
            return $example;
        }
        
        return null;
    }
}

