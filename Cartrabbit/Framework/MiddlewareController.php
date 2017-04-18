<?php
namespace Cartrabbit\Framework;

abstract class MiddlewareController {

    protected $controller;
    protected $middlewares;
    /**
     * Called by Controller to run Middleware.
     *
     * @param  Controller Object
     * @param  array $middlewares
     * @return mixed
     */
    public function run($controller, $middlewares)
    {
        $this->controller = $controller;
        $this->middlewares = $middlewares;
        return $this->handle();
    }

    /**
     * Calls the next Middleware.
     *
     * @param  array $middlewares
     * @return void
     */
    public function next()
    {
        $this->controller->next($this->middlewares);
    }

    /**
     * Method to be implemented by each Middleware.
     *
     * @return mixed
     */
    abstract function handle();
}