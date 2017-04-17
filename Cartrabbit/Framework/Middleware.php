<?php
namespace Cartrabbit\Framework;

use Illuminate\Http\Request;

abstract class Middleware {
    /**
     * @var Cartrabbit\Framework\Router
     */
    protected $router;

    /**
     * @var array
     */
    protected $store;
    
    /**
     * Called by WP_Router to run Middleware.
     *
     * @param  Illuminate\Http\Request
     * @param  Cartrabbit\Framework\Router
     * @param  array
     * @return mixed
     */
    public function run( Request $request, Router $router, $store )
    {
        $this->router = $router;
        $this->store  = $store;
        return $this->handle( $request );
    }

    /**
     * Calls the next Middleware.
     *
     * @param  Illuminate\Http\Request
     * @return void
     */
    public function next( Request $request )
    {
        $this->router->next( $request, $this->router, $this->store );
    }

    /**
     * Method to be implemented by each Middleware.
     *
     * @param  Illuminate\Http\Request
     * @return mixed
     */
    abstract function handle( Request $request );
}