<?php
namespace Cartrabbit\Framework\Views;

use Cartrabbit\Framework\Application;

class HtmlView{
    public static $instance = null;

    public $_data = null;

    protected $current_path = null;

    protected $plugin_folder = array();

    public function __construct($properties=null) {

    }

    public static function getInstance(array $config = array())
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * load view
     * */
    public function loadView($name, $vars){
        $this->_data = $vars;
        $views = $this->getViews();

        $viewNameAsArray = explode('/', $name);
        $viewName = trim($viewNameAsArray['0'], '@');
        if(isset($views[$viewName])){
            $path = str_replace($viewNameAsArray['0'], '', $name);
            $path = $views[$viewName].$path;
            $this->current_path = $path;
            if(file_exists($path)){
                ob_start();
                include($path);
                $html = ob_get_contents();
                ob_end_clean();
                return $this->response($html);
            }
            return $this->response("File doesn't exists", 0);
        } else {
            return $this->response("No view found", 0);
        }
    }

    /**
     * To load views
     * */
    protected function getViews(){
        $instance = Application::getInstance();
        $plugins = $instance->getPlugins();
        $views = array();
        foreach ($plugins as $plugin){
            $config = $plugin->getConfig();
//            $basePath = $plugin->getBasePath();
//            echo $basePath;exit;
            $pluginViews = $config['views'];
            $views = array_merge($views, $pluginViews);
        }

        return $views;
    }

    /**
     * Load another file
     * */
    protected function loadTemplate($name){
        $filePath = dirname($this->current_path);
        $file = $filePath.'/'.$name;
        if(file_exists($file)){
            ob_start();
            include($file);
            $html = ob_get_contents();
            ob_end_clean();
            echo $html;
        }
    }

    /**
     * Get Template view
     * */
    protected function getTemplateView(){

    }

    /**
     * send response
     * */
    protected function response($body, $status = 200, $headers = null){
        return new \Cartrabbit\Framework\Response($body, $status, $headers);
    }
}