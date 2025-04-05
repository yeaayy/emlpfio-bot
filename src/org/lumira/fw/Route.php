<?php

namespace org\lumira\fw;

use org\lumira\Errors\MethodNotAllowed;
use org\lumira\Errors\NotFound;
use org\lumira\fw\MethodHandler;

class Route {
    /** @var array<MethodHandler> */
    private $methodHandlers = [];

    /** @var array<Route> */
    private $subpaths = [];

    /** @var array<Route> */
    private $namedSubpaths = [];

    /** @var array<string> */
    private $middleware;

    function __construct(array $middleware = []) {
        $this->middleware = $middleware;
    }

    function handle($paths, $method, Request $req) {
        if (count($paths) === 0) {
            if (!key_exists($method, $this->methodHandlers)) {
                throw new MethodNotAllowed();
            }

            return $this->methodHandlers[$method]->run($req);
        }

        $segment = array_shift($paths);
        if (key_exists($segment, $this->subpaths)) {
            return $this->subpaths[$segment]->handle($paths, $method, $req);
        }

        foreach ($this->namedSubpaths as $name => $handler) {
            $copyReq = clone $req;
            $copyReq[$name] = $segment;
            try {
                return $handler->handle($paths, $method, $copyReq);
            } catch(NotFound $e) {}
        }

        throw new NotFound();
    }

    function addSubpath(string $subpath) {
        if (key_exists($subpath, $this->subpaths)) {
            return $this->subpaths[$subpath];
        }
        $handler = new Route($this->middleware);
        $this->subpaths[$subpath] = $handler;
        return $handler;
    }

    function addNamedSubpath(string $name) {
        if (key_exists($name, $this->namedSubpaths)) {
            return $this->namedSubpaths[$name];
        }
        $handler = new Route($this->middleware);
        $this->namedSubpaths[$name] = $handler;
        return $handler;
    }

    function addSubpaths(string $subpaths) {
        $segments = explode('/', trim($subpaths, "/\n\r\t\v\0"));

        // Remove empty segment
        $segments = array_filter($segments, function($segment) {
            return $segment != '';
        });

        $current = $this;
        foreach ($segments as $segment) {
            if (str_starts_with($segment, ':')) {
                $current = $current->addNamedSubpath(substr($segment, 1));
            } else {
                $current = $current->addSubpath($segment);
            }
        }
        return $current;
    }

    function addMethodHandler(string $method, callable | array $handler) {
        $method = strtoupper($method);
        if (key_exists($method, $this->methodHandlers)) {
            return $this->methodHandlers[$method];
        }
        return $this->methodHandlers[$method] = new MethodHandler($handler, $this->middleware);
    }

    function middleware(string ...$middleware)
    {
        array_push($this->middleware, ...$middleware);
        return $this;
    }

    function get($path, $handler = null) {
        if (gettype($path) === 'string') {
            return $this->addSubpaths($path)->addMethodHandler('GET', $handler);
        } else {
            return $this->addMethodHandler('GET', $path);
        }
    }

    function post($path, $handler = null) {
        if (gettype($path) === 'string') {
            return $this->addSubpaths($path)->addMethodHandler('POST', $handler);
        } else {
            return $this->addMethodHandler('POST', $path);
        }
    }

    function put($path, $handler = null) {
        if (gettype($path) === 'string') {
            return $this->addSubpaths($path)->addMethodHandler('PUT', $handler);
        } else {
            return $this->addMethodHandler('PUT', $path);
        }
    }

    function delete($path, $handler = null) {
        if (gettype($path) === 'string') {
            return $this->addSubpaths($path)->addMethodHandler('DELETE', $handler);
        } else {
            return $this->addMethodHandler('DELETE', $path);
        }
    }

    function patch($path, $handler = null) {
        if (gettype($path) === 'string') {
            return $this->addSubpaths($path)->addMethodHandler('PATCH', $handler);
        } else {
            return $this->addMethodHandler('PATCH', $path);
        }
    }

    function group($path, $group) {
        $group($this->addSubpaths($path));
        return $this;
    }

    function resources($path, $name, $cls) {
        $this->group($path, function (Route $route) use ($name, $cls) {
            $route->get([$cls, 'getAll']);
            $route->post([$cls, 'insert']);
            $route->get(':'.$name, [$cls, 'get']);
            $route->post(':'.$name, [$cls, 'update']);
            $route->delete(':'.$name, [$cls, 'delete']);
        });
        return $this;
    }

    function print(string $prefix = '') {
        if (count($this->methodHandlers) > 0) {
            printf("%s {", $prefix == '' ? '/' : $prefix);
            $first = true;
            foreach ($this->methodHandlers as $method => $handler) {
                if (!$first) {
                    echo '|';
                }
                $first = false;
                echo $method;
            }
            echo "}\n";
        }

        foreach ($this->subpaths as $name => $handler) {
            $handler->print(sprintf("%s/%s", $prefix, $name));
        }

        foreach ($this->namedSubpaths as $name => $handler) {
            $handler->print(sprintf("%s/:%s", $prefix, $name));
        }
    }
}
