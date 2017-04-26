<?php

namespace Cartrabbit\Framework\View;

use Cartrabbit\Framework\Application;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

class ViewFinder extends FileViewFinder
{
    public function __construct()
    {
        $app = Application::getInstance();
        $this->app = $app;
    }
    /**
     * Return a list of found views.
     *
     * @return array
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * Get the fully qualified location of the view.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }
        if ($this->hasHintInformation($name = trim($name))) {
            $segments = explode(static::HINT_PATH_DELIMITER, $name);
            if(isset($this->app['path.'.$segments[0]])){
                $this->hints[$segments[0]] = $this->app['path.'.$segments[0]];
            }
            return $this->views[$name] = $this->findNamespacedView($name);
        }
        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    /**
     * Find the given view in the list of paths.
     *
     * @param  string  $name
     * @param  array   $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if (file_exists($viewPath = $path.'/'.$file)) {
                    return $viewPath;
                }
            }
        }

        throw new InvalidArgumentException("View [$name] not found.");
    }
}
