<?php

namespace Cartrabbit\Framework\Providers;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Cartrabbit\Framework\View\Loop;
use Cartrabbit\Framework\View\ViewFinder;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBladeEngine('blade', new EngineResolver);
        $this->registerPhpEngine('php', new EngineResolver);
        $this->registerEngineResolver();
        $this->registerViewFactory();
        $this->registerLoop();
    }

    /**
     * Register the EngineResolver instance to the application.
     */
    protected function registerEngineResolver()
    {
        $serviceProvider = $this;

        $this->app->singleton('view.engine.resolver', function () use ($serviceProvider) {
            $resolver = new EngineResolver();

            // Register the engines.
            foreach (['php', 'blade'] as $engine) {
                $serviceProvider->{'register'.ucfirst($engine).'Engine'}($engine, $resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the PHP engine to the EngineResolver.
     *
     * @param string                                  $engine   Name of the engine.
     * @param \Illuminate\View\Engines\EngineResolver $resolver
     */
    protected function registerPhpEngine($engine, EngineResolver $resolver)
    {
        $resolver->register($engine, function () {
            return new PhpEngine();
        });
    }

    /**
     * Register the Blade engine to the EngineResolver.
     *
     * @param string                                  $engine   Name of the engine.
     * @param \Illuminate\View\Engines\EngineResolver $resolver
     */
    protected function registerBladeEngine($engine, EngineResolver $resolver)
    {
        $container = $this->app;
//        $storage = $container['path.storage'].'views'.THEMOSIS_STORAGE;
        $storage = CARTRABBIT_STORAGE;
        $filesystem = $container['filesystem'];
//        $filesystem = new Filesystem();

        $bladeCompiler = new BladeCompiler($filesystem, $storage);
        $this->app->instance('blade', $bladeCompiler);

        $resolver->register($engine, function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });
    }


    /**
     * Register the view factory. The factory is
     * available in all views.
     */
    protected function registerViewFactory()
    {
        $container = $this->app;
        // Register the View Finder first.
        $this->app->singleton('view.finder', function ($app){
            return new ViewFinder($app['filesystem'], $app['paths'], ['blade.php', 'php']);
        });

        $this->app->singleton('view', function ($app) {
            $factory = new Factory($app['view.engine.resolver'], $app['view.finder'], $app['events']);
            // Set the container.
            $factory->setContainer($app);
            // Tell the factory to also handle the scout template for backwards compatibility.
            $factory->addExtension('php', 'blade');
            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });
    }

    /**
     * Register the loop helper class.
     */
    protected function registerLoop()
    {
        $this->app->instance('loop', new Loop());
    }

    /**
     * Register custom Blade directives for use into views.
     */
    public function boot()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

    }
}
