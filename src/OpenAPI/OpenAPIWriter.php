<?php

namespace Mpociot\ApiDoc\OpenAPI;

use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;

class OpenAPIWriter
{
    protected $routes;

    public function __construct(Collection $routes)
    {
        $this->routes = $routes;
    }

    public function getDocument()
    {
        $skeleton = $this->getBaseYAML();
        $tags = $this->getRoutesGroups();

        $skeleton['tags'] = $this->getRoutesGroups();
        $skeleton['paths'] = $this->getRoutesPaths();

        $yaml = Yaml::dump($skeleton);

        dd($yaml);

        return $yaml;
    }

    private function getBaseYAML()
    {
        return [
            'openapi' => '3.0.0',
            'info'    => [
                'title' => 'My OpenAPI 3.0.0 document',
                'version' => '1.0'
            ],
            'paths' => []
        ];
    }

    private function getRoutesGroups()
    {
        return array_keys($this->routes->map(function ($route, $group) {
            return $group;
        })->toArray());
    }

    /*
     * TODO: define entry skeleton and each of them
     * with single methods.
     */
    private function getRoutesPaths()
    {
        $routes = [];

        $this->routes->each(function ($group) use (&$routes) {
            $group->each(function ($route) use (&$routes) {
                foreach ($route['methods'] as $method) {
                    $operationId = 'operationId';
                    $summary = 'summary';
                    $uri = [
                        'responses' => [
                            '200' => [
                                'description' => 'wubba'
                            ],
                        ],
                    ];

                    isset($routes[$route['uri']]) ?: $routes[$route['uri']] = [];

                    $routes[$route['uri']][$method] = [
                        'operationId' => $operationId,
                        'summary' => $summary,
                    ];
                }
            });
        });

        return $routes;
    }
}
