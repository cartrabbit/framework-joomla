<?php
namespace Cartrabbit\Framework;

use Illuminate\Http\Request;

abstract class Middleware {

    protected $controller;
    protected $middlewares;
    /**
     * Called by Controller to run Middleware.
     *
     * @param  Controller Object
     * @param  array $middlewares
     * @return mixed
     */
    public function run(Request $request, $controller, $middlewares)
    {
        $this->controller = $controller;
        $this->middlewares = $middlewares;
        return $this->handle($request);
    }

    /**
     * Calls the next Middleware.
     *
     * @param  array $middlewares
     * @return void
     */
    public function next(Request $request)
    {
        $this->controller->next($request, $this->middlewares);
    }

    /**
     * Method to be implemented by each Middleware.
     *
     * @return mixed
     */
    abstract function handle(Request $request);
}