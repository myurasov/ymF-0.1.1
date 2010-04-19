<?php

/**
 * ym Framework main file
 *
 * @copyright Misha Yurasov 2009-2010
 * @package ymF
 */

namespace ymF;

class Exception extends \Exception {}

/**
 * ymF Kernel class
 *
 */
class Kernel
{
  // Root namespaces registered for autoloading
  private static $autoload = array();

  /**
   * Initialize engine
   *
   */
  public static function init()
  {
    // Define version

    // major.minor<.change> <status>
    define('ymF\VERSION', '0.1.0 beta');

    // Define paths:

    // Project root directory
    define('ymF\PATH_ROOT', realpath(__DIR__ . '/../../..'));

    // Core executable files
    define('ymF\PATH_CORE', PATH_ROOT . '/core');

    // Web documents
    define('ymF\PATH_WWW', PATH_ROOT . '/www');

    // Command-line interface
    define('ymF\PATH_CLI', PATH_ROOT . '/cli');

    // Variable application data
    define('ymF\PATH_DATA', PATH_ROOT . '/data');

    // Temporary data
    define('ymF\PATH_TEMP', PATH_DATA . '/temp');

    // Code modules and root namespace
    define('ymF\PATH_MODULES', PATH_CORE . '/modules');

    // Templates
    define('ymF\PATH_TEMPLATES', PATH_CORE . '/templates');

    // Resource files
    define('ymF\PATH_RESOURCES', PATH_CORE . '/resources');

    // Bundled libraries
    define('ymF\PATH_LIBRARIES', PATH_CORE . '/libraries');

    // Config classes
    define('ymF\PATH_CONFIGURATION', PATH_CORE . '/configs');

    // Logs
    define('ymF\PATH_LOGS', PATH_DATA . '/logs');

    // Define errors:

    define('ymF\ERROR_OK', 0);
    define('ymF\ERROR_MISC', -1);

    // Register autoloading for current
    self::registerAutoloadNamespace(__NAMESPACE__);

    // Register autoloader function
    spl_autoload_register(__CLASS__ . '::autoload');
  }

  /**
   * Get library path
   *
   * @param string $library
   * @return string
   */
  public static function getLibraryPath($library)
  {
    $path = Config::get('libraries.' . $library);

    if (substr($path, 0, 1) == '/')
    {
      return $path;
    }
    else
    {
      return PATH_LIBRARIES . '/' . $path;
    }
  }

  /**
   * Register namespace for autoloading
   *
   * @param <type> $namespace
   * @param <type> $root
   * @param <type> $relocate_config
   */
  public static function registerAutoloadNamespace(
    $namespace, $root = null, $relocate_config = true)
  {
    self::$autoload[$namespace] = array(
      'root'          => is_null($root) ? PATH_MODULES : $root,
      'config_reloc'  => $relocate_config
    );
  }

  /**
   * Loads required class on first usage
   *
   * Something\Another\Config - from core\configs\Something.Another.Config.php
   * Something\Another\Class - from core\modules\Something\Another\Class.php
   *
   * @param string $class_name
   */
  public static function autoload($class_name)
  {
    // Load only registered namespaces

    $registered = false;

    foreach (self::$autoload as $namespace => $options)
    {
      if (substr($class_name, 0, strlen($namespace)) == $namespace)
      {
        $registered = true;
        break;
      }
    }

    if ($registered)
    {
      // Check class name for double slashes
      // (Valid in file path, but invalid in classs names)

      if (preg_match('#[\\\\\\/][\\\\\\/]#', $class_name))
        throw new Exception("Invalid class name '$class_name'", ERROR_MISC);

      if ($options['config_reloc'] && $class_name === 'Config' || substr($class_name, -7) == '\\Config')
      {
        // Load configs from
        // Kernel\configs\namespace.subnamespace.Config.php

        $path = PATH_CONFIGURATION . '/' .
          str_replace('\\', '.', $class_name) . '.php';
      }
      else
      {
        // Full path to class file

        $path = $options['root'] . '/' . str_replace('\\', '/', $class_name) . '.php';
      }

      // Include file

      if (!file_exists($path) || !include_once($path))
        return false;

      return true;
    }

    return false;
  }
}

Kernel::init();