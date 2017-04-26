<?php
/**
 * Cartrabbit - A PHP Framework For Joomla
 *
 * @package  Cartrabbit
 * @author   Ashlin <ashlin@flycart.org>
 * Based on Herbert Framework
 */

/**
 * Ensure this is only ran once.
 */
if (defined('CARTRABBIT_AUTOLOAD'))
{
    return;
}

define('CARTRABBIT_AUTOLOAD', microtime(true));

defined('CARTRABBIT_STORAGE') ? CARTRABBIT_STORAGE : define('CARTRABBIT_STORAGE', JPATH_ROOT.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'storage');

if (!file_exists(CARTRABBIT_STORAGE)) {
    mkdir(CARTRABBIT_STORAGE, 0777, true);
}

@require 'helpers.php';

/**
 * Get Cartrabbit.
 */
global $cartrabbit;
$cartrabbit = Cartrabbit\Framework\Application::getInstance();

function loadCartRabbitApps($iterator, $type = 'components', $parentPlugin = ''){
    global $cartrabbit;
    foreach ($iterator as $directory) {

        if ( ! $directory->valid() || $directory->isDot() || ! $directory->isDir()) {
            continue;
        }
        $root = $directory->getPath() . '/' . $directory->getFilename();

        if ( ! file_exists($root . '/cartrabbit.config.php')) {
            continue;
        }
        $fileName = explode('_', $directory->getFilename());

        if($type == 'plugins'){
            if (!JPluginHelper::isEnabled($parentPlugin, $directory->getFilename())) {
                continue;
            }
        } else {
            if (!JComponentHelper::isEnabled($directory->getFilename(), true)) {
                continue;
            }
        }

        $fileName = isset($fileName[1])? $fileName[1]: $fileName[0];
        $config = $cartrabbit->getPluginConfig($root);

        $plugin = substr($root . '/'.$fileName.'.php', strlen(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'));
        $plugin = ltrim($plugin, '/');

        if ( ! $cartrabbit->pluginMatches($config)) {
            $cartrabbit->pluginMismatched($root);
        }

        // To register the namespace
        $loader = new \Composer\Autoload\ClassLoader();
        $loader->addPsr4($config['namespace'].'\\', $root.'/app');
        // activate the autoloader
        $loader->register();
        // to enable searching the include path (eg. for PEAR packages)
        $loader->setUseIncludePath(true);


        $cartrabbit->pluginMatched($root);
        $cartrabbit->loadPlugin($config);
        $cartrabbit->activatePlugin($root);

        // To register the plugin
        $activePlugin = new \Cartrabbit\Framework\Base\Plugin($root.'/');
        $cartrabbit->registerPlugin($activePlugin);

        if ( ! $cartrabbit->pluginMatches($config))
        {
            $cartrabbit->pluginMismatched($root);

            continue;
        }
        $cartrabbit->pluginMatched($root);

        $cartrabbit->loadPlugin($config);

        $cartrabbit->registerAllPaths($config['views']);
    }
}
/**
 * Load all cartrabbit apps from component roots.
 */
$iterator = new DirectoryIterator(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components');
loadCartRabbitApps($iterator);

/**
 * Load all cartrabbit apps from plugin roots.
 */
$iteratorPluginRoot = new DirectoryIterator(JPATH_SITE.DIRECTORY_SEPARATOR.'plugins');
foreach ($iteratorPluginRoot as $iteratorPlugindirectory){
    if ( ! $iteratorPlugindirectory->valid() || $iteratorPlugindirectory->isDot() || ! $iteratorPlugindirectory->isDir()){
        continue;
    }
    $iterator = new DirectoryIterator(JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$iteratorPlugindirectory->getFilename());
    loadCartRabbitApps($iterator, 'plugins', $iteratorPlugindirectory->getFilename());
}

/**
 * Boot Cartrabbit.
 */
$cartrabbit->boot();