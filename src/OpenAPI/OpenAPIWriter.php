<?php

namespace Mpociot\ApiDoc\OpenAPI;

use Illuminate\Support\Collection;

class OpenAPIWriter
{
    protected $routes;

    public function __construct(Collection $routes)
    {
        $this->routes = $routes;
    }

    public function getDocument()
    {
        return ['this is yaml, trust me.'];
    }
}
