<?php namespace Cartrabbit\Framework;

use ArrayObject;
use Exception;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Routing\Pipeline;
use J2Store\Helper\J2Store;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class Route
{

    /**
     * @var \Cartrabbit\Framework\Application
     */
    protected $app;

    protected $_namespace = 'J2Store';
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $uses;

    /**
     * @param \Cartrabbit\Framework\Application $app
     * @param                                $data
     * @param                                $parameters
     */
    public function __construct(Application $app, $data = array(), $parameters = [])
    {
        $this->app = $app;
        if(empty($data['uses'])){
            $data = $this->getRouteData();
        }
        $this->parameters = $parameters;
        $this->uri = (isset($data['uri']) && !empty($data['uri'])) ? $data['uri']: '';
        $this->name = array_get($data, 'as', $this->uri);
        $this->uses = (isset($data['uses']) && !empty($data['uses'])) ? $data['uses']: '';

    }

    function getRouteData(){
        $data = array(
            'uri' => '',
            'uses' => ''
        );
        $platform = J2Store::platform();
        $request = $platform->getRequest();
        $post = $request->all();
        if (strpos($_SERVER['REQUEST_URI'], '/api/v1') !== false) {
            //api request
        } elseif (isset($post['option']) && $post['option'] == \J2Store\Helper\J2Store::getJ2Option() && isset($post['view']) && !empty($post['view'])) {
            $view = (isset($post['view']) && !empty($post['view'])) ? $post['view']: 'dashboard';
            $task = (isset($post['task']) && !empty($post['task'])) ? $post['task']: 'index';
            $is_site = (isset($post['is_site']) && !empty($post['is_site'])) ? $post['is_site']: 0;
            if ($view != '') {
                if ($platform->isSite() || $is_site) {
                    $className = '\\' . $this->_namespace . '\\Controllers\\Site\\' . ucfirst($view);
                } else {
                    $className = '\\' . $this->_namespace . '\\Controllers\\' . ucfirst($view);
                }
                if (!class_exists($className)) {
                    $className = '\\' . $this->_namespace . '\\Controllers\\' . ucfirst('dashboard');
                    $task = 'index';
                }
                if (class_exists($className)) {
                    $data['uses'] = $className.'@'.$task;
                }
            }
        }
        return $data;
    }

    function run(){
        $router = new \Illuminate\Routing\Route($this->getMethods(), $this->uri, $this->uses);
        $router = $router->bind($this->app['request'])->setContainer($this->app);
        $middleware = $router->gatherMiddleware();
        $middlewares = $this->getMiddlewares($middleware);
        return (new Pipeline($this->app))
            ->send($this->app['request'])
            ->through($middlewares)
            ->then(function ($request) use ($router) {
                return $this->toResponse(
                    $request, $router->run()
                );
            });
    }

    function getMiddlewares($route_middleware = array()){
        $app_middlewares = $this->app->getMiddlewares();
        $final_middleware = array();
        foreach ($route_middleware as $middle) {
            if (isset($app_middlewares[$middle]) && !empty($app_middlewares[$middle])) {
                $final_middleware[] = $app_middlewares[$middle];
            }
        }
        return $final_middleware;
    }

    function getMethods(){
        return array('GET', 'POST', 'PUT');
    }
    /**
     * Get a single parameter.
     *
     * @param       $name
     * @param mixed $default
     * @return mixed
     */
    public function parameter($name, $default = null)
    {
        if (!isset($this->parameters[$name])) {
            return $default;
        }
        return $this->parameters[$name];
    }

    /**
     * Static version of prepareResponse.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $response
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    function toResponse($request, $response)
    {
        if ($response instanceof \Illuminate\Contracts\Support\Responsable) {
            $response = $response->toResponse($request);
        }
        if ($response instanceof PsrResponseInterface) {
            $response = (new HttpFoundationFactory)->createResponse($response);
        } elseif ($response instanceof \Illuminate\Database\Eloquent\Model && $response->wasRecentlyCreated) {
            $response = new \Illuminate\Http\JsonResponse($response, 201);
        } elseif (!$response instanceof \Symfony\Component\HttpFoundation\Response &&
            ($response instanceof \Illuminate\Contracts\Support\Arrayable ||
                $response instanceof \Illuminate\Contracts\Support\Jsonable ||
                $response instanceof ArrayObject ||
                $response instanceof JsonSerializable ||
                is_array($response))) {
            $response = new \Illuminate\Http\JsonResponse($response);
        } elseif (!$response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response = new \Illuminate\Http\Response($response);
        }
        if ($response->getStatusCode() === \Illuminate\Http\Response::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }
        return $response->prepare($request);
    }

}
