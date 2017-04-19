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
$cartrabbit = Cartrabbit\Framework\Application::getInstance();

/**
 * Load all cartrabbit.php files in plugin roots.
 */
$iterator = new DirectoryIterator(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components');

foreach ($iterator as $directory)
{
    
    if ( ! $directory->valid() || $directory->isDot() || ! $directory->isDir())
    {
        continue;
    }
    $root = $directory->getPath() . '/' . $directory->getFilename();

    if ( ! file_exists($root . '/cartrabbit.config.php')) {
        continue;
    }
    $fileName = explode('_', $directory->getFilename());

    if (!JComponentHelper::isEnabled($directory->getFilename(), true)) {
        continue;
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

/**
 * Boot Cartrabbit.
 */
$cartrabbit->boot();
