<?php

namespace org\lumira\fw;

class MethodHandler {
    private $middleware;
    private $handler;

    function __construct(callable | array $handler, array $middleware = []) {
        $this->handler = $handler;
        $this->middleware = $middleware;
    }

    function middleware(...$middleware)
    {
        array_push($this->middleware, ...$middleware);
        return $this;
    }

    function run(Request $req, $i = 0)
    {
        if ($i == count($this->middleware)) {
            return $this->handle($req);
        }
        return (new $this->middleware[$i])->handle($req, function () use ($req, $i) {
            return $this->run($req, $i + 1);
        });
    }

    function handle(Request $req)
    {
        if (gettype($this->handler) === 'array') {
            return (new $this->handler[0])->{$this->handler[1]}($req);
        } else {
            return ($this->handler)($req);
        }
    }
}
