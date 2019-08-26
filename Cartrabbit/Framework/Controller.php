<?php namespace Cartrabbit\Framework;

use InvalidArgumentException;
use Illuminate\Http\Request;

class Controller extends \Illuminate\Routing\Controller
{
    protected $app;
    protected $middlewares = array();
    protected $allow_access = false;

    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        // To handle middleware
        if (!empty($this->middlewares)) {
            $this->next($request, $this->middlewares, true);
            if (!$this->allow_access) {
                $this->handleMiddlewareFailure();
            }
        }
    }

    /**
     * Handle the next / request response
     * */
    public function next($request, $middlewares, $first = false)
    {
        if ((isset($middlewares[0]) && $first) || isset($middlewares[1])) {
            if (!$first) {
                array_shift($middlewares);
            }
            $this->fetch($middlewares[0] . '@run', array($request, $this, $middlewares));
        } else {
            $this->allow_access = true;
        }
    }

    /**
     * Fetches a controller or callbacks response.
     *
     * @param $callback
     * @param array $args
     * @return mixed
     */
    public function fetch($callback, $args = array())
    {
        if (is_string($callback)) {
            list($class, $method) = explode('@', $callback, 2);
            $middlewares = $this->app->getMiddlewares();
            if (isset($middlewares[$class])) {
                $class = $middlewares[$class];
            }
            if (class_exists($class)) {
                $controller = new $class;
            } else {
                throw new InvalidArgumentException("Middleware {$class} not defined");
            }
            return call_user_func_array(array($controller, $method), $args);
        }
        return call_user_func_array($callback, $args);
    }

    /**
     * To handle middleware failure
     * */
    public function handleMiddlewareFailure()
    {
        die;
    }
}