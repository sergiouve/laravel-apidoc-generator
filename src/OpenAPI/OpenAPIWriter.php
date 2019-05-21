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

        $tags = $this->getTags();
        $paths = $this->hydratePaths($this->getPaths());

        $skeleton['tags'] = $tags;
        $skeleton['paths'] = $paths;

        $yaml = Yaml::dump($skeleton);

        return $yaml;
    }

    private function getBaseYAML()
    {
        return [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'My OpenAPI 3.0.0 document',
                'version' => '1.0'
            ],
            'contact' => [
                'example@domain.com',
            ],
            'license' => [
                'name' => 'Apache 2.0',
                'url' => 'http://www.apache.org/licenses/LICENSE-2.0.html',
            ],
            'tags'        => [],
            'paths'       => [],
            'definitions' => [],
        ];
    }

    private function getBasePath()
    {
        return [
            '{{routePath}}' => [
                'operationId' => '',
                'summary'     => '',
                'responses'   => [],
            ],
        ];
    }

    private function getBaseResponse()
    {
        return [
            '{{httpCode}}' => [
                'description' => '',
                'content' => '',
            ],
        ];
    }

    private function getTags()
    {
        return $this->routes->map(function ($route) {
            return [
                'name'        => $route['group'],
                'description' => $route['description'],
            ];
        })->unique('name')->toArray();
    }

    private function getPaths()
    {
        return $this->routes->map(function ($route) {
            return $route['uri'];
        })->unique();
    }

    private function hydratePaths(Collection $paths)
    {
        return $paths->map(function ($path) {
            $methods = $this->getPathMethods($path);
            return [
                $path => [
                    $methods[0] => []
                ]
            ];
        })->toArray();
    }

    private function getPathMethods(String $path)
    {
        return $this->routes->filter(function ($route) use ($path) {
            return $route['uri'] === $path;
        })->map(function ($route) {
            return strtolower($route['methods'][0]);
        });
    }
}
