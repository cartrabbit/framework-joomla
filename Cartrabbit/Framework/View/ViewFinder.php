<?php

namespace Cartrabbit\Framework\View;

use Illuminate\View\FileViewFinder;

class ViewFinder extends FileViewFinder
{
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
            return $this->views[$name] = $this->findNamespacedView($name);
        }
        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }
}
