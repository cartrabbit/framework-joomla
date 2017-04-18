<?php namespace Cartrabbit\Framework;

use InvalidArgumentException;

/**
 * @method void get()    get(array $parameters)    Adds a get route.
 * @method void post()   post(array $parameters)   Adds a post route.
 * @method void put()    put(array $parameters)    Adds a put route.
 * @method void patch()  patch(array $parameters)  Adds a patch route.
 * @method void delete() delete(array $parameters) Adds a delete route.
 */
class Controller {
    protected $app;
    protected $middlewares = array();

    public function __construct(Application $app)
    {
        $this->app = $app;
        // To handle middleware
        if(!empty($this->middlewares)){
            return $this->next( $this->middlewares, true );
        }
    }

    /**
     * Handle the next / request response
     * */
    public function next($middlewares, $first = false )
    {
        if ( (isset( $middlewares[0] ) && $first) || isset( $middlewares[1] ) )
        {
            if ( !$first )
            {
                array_shift( $middlewares );
            }
            $this->fetch( $middlewares[0] .'@run', array($this, $middlewares));
        } else {
            return ;
        }
    }

    /**
     * Fetches a controller or callbacks response.
     *
     * @param $callback
     * @param array $args
     * @return mixed
     */
    public function fetch( $callback, $args = array() )
    {
        if ( is_string( $callback ) )
        {
            list( $class, $method ) = explode( '@', $callback, 2 );
            $middlewares = $this->app->getMiddlewares();
            if(isset($middlewares[$class])){
                $class = $middlewares[$class];
            }
            if(class_exists($class)){
                $controller = new $class;
            } else {
                throw new InvalidArgumentException("Middleware {$class} not defined");
            }
            return call_user_func_array( array( $controller, $method ), $args );
        }
        return call_user_func_array( $callback, $args );
    }
}