<?php

namespace Mpociot\ApiDoc\OpenAPI;

use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;

class OpenAPIWriter
{
    const YAML_INLINE_LEVEL = 16;

    protected $routes;

    public function __construct(Collection $routes)
    {
        $this->routes = $routes;
    }

    public function generate()
    {
        $skeleton = $this->getBaseYAML();
        $document = $this->hydrateSkeleton($skeleton);
        $yaml = Yaml::dump($document, self::YAML_INLINE_LEVEL);

        dd($yaml);

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
            'operationId' => '',
            'summary'     => '',
            'responses'   => [
                $this->getBaseResponse('200'),
            ],
        ];
    }

    private function getBaseResponse(String $httpCode)
    {
        return [
            $httpCode => [
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

    private function getPathMethods(String $path)
    {
        return $this->routes->filter(function ($route) use ($path) {
            return $route['uri'] === $path;
        })->map(function ($route) {
            return strtolower($route['methods'][0]);
        })->reduce(function ($methods, $method) {
            return $methods->put($method, $this->getBasePath());
        }, new Collection());
    }

    private function hydrateSkeleton(array $skeleton)
    {
        $skeleton['tags'] = $this->getTags();
        $skeleton['paths'] = $this->hydratePaths($this->getPaths());
        $skeleton['definitions'] = $this->hydrateDefinitions();

        return $skeleton;
    }

    private function hydratePaths(Collection $paths)
    {
        return $paths->reduce(function ($hydrated, $path) {
            return $hydrated->put($path, $this->getPathMethods($path));
        }, new Collection())->toArray();
    }

    private function hydrateDefinitions()
    {
        return $this->routes->map(function ($route) {
            return [
                'data' => $route['response'][0],
                'uri'  => $route['uri'],
            ];
        })->filter(function ($response) {
            return (bool) $response['data']['content'];
        })->reduce(function ($definitions, $definition) {
            return $definitions->put($this->generateDefinitionName($definition), [
                'type' => 'object',
                'properties' => $this->getDefinitionProperties($definition),
            ]);
        }, new Collection())->toArray();
    }

    private function generateDefinitionName($definition)
    {
        return str_replace('/', '', ucwords($definition['uri'], '/')) . $definition['data']['status'];
    }

    private function getDefinitionProperties($definition)
    {
        $properties = json_decode($definition['data']['content'], true);

        return $this->hydrateDefinition(collect($properties));
    }

    private function hydrateDefinition(Collection $properties)
    {
        return $properties->map(function ($property) {
            return [
                'type' => gettype($property),
                'example' => $property,
            ];
        })->toArray();
    }
}
