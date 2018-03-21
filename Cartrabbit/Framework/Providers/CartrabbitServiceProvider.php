<?php namespace Cartrabbit\Framework\Providers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Events\Dispatcher;

class CartrabbitServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEloquent();

        $this->app->instance(
            'env',
            defined('CARTRABBIT_ENV') ? CARTRABBIT_ENV
                : (defined('WP_DEBUG') ? 'local'
                    : 'production')
        );

        $this->app->bind('filesystem', function () {
            return new Filesystem();
        });

        $this->app->bind('events', function ($container) {
            return new Dispatcher($container);
        });

        $this->app->instance(
            'router',
            $this->app->make('Cartrabbit\Framework\Router', ['app' => $this->app])
        );

        $this->app->instance(
            'url',
            $this->app->make('Illuminate\Routing\UrlGenerator', ['app' => $this->app])
        );

        $this->app->bind(
            'route',
            'Cartrabbit\Framework\Route'
        );

        $this->app->instance(
            'session',
            $this->app->make('Symfony\Component\HttpFoundation\Session\Session', ['app' => $this->app])
        );

        $this->app->alias(
            'session',
            'Symfony\Component\HttpFoundation\Session\Session'
        );

        $this->app->singleton(
            'errors',
            function ()
            {
                return session_flashed('__validation_errors', []);
            }
        );

        $_GLOBALS['errors'] = $this->app['errors'];
    }

    /**
     * Registers Eloquent.
     *
     * @return void
     */
    protected function registerEloquent()
    {
        $capsule = new Capsule($this->app);
        $config = \JFactory::getConfig();
        $db = \JFactory::getDbo();
        $driver = $db->serverType;
        if(!isset($db->serverType) || (!in_array($db->serverType,array('mysql','pgsql','sqlite','sqlsrv')))){
            $driver = 'mysql';
        }
        $capsule->addConnection([
            'driver' => $driver,
            'host' => $config->get('host'),
            'database' => $config->get('db'),
            'username' => $config->get('user'),
            'password' => $config->get('password'),
            'charset' => 'utf8',//'utf8',
            'collation' => 'utf8_unicode_ci',//'utf8mb4',
            'prefix' => $config->get('dbprefix')
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * Boots the service provider.
     *
     * @return void
     */
    public function boot()
    {
//        $this->app['session']->start();
    }

}
