<?php

/**
 * Twig service
 *
 * Requires Twig library, version 0.9.3+
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF\Services;

use ymF\Config;
use ymF\Kernel;

class TwigService
{
  /**
   * @var Twig_Environment
   */
  private static $twig;

  /**
   * Loads Twig libary and registers it's class autoloader
   *
   * @staticvar boolean $loaded
   */
  public static function loadTwig()
  {
    if (!class_exists('Twig_Autoloader', false))
    {
      // Load Twig library
      require_once Kernel::getLibraryPath('Twig') . '/Autoloader.php';

      // Register twig autoloader
      \Twig_Autoloader::register();
    }
  }

  /**
   * @return Twig_Environment
   */
  public static function getTwig()
  {
    if (is_null(self::$twig))
    {
      // Load Twig libary
      self::loadTwig();

      // Create Twig
      self::_createTwig();
    }

    return self::$twig;
  }

  /**
   * Get Twig templates directory path
   *
   * @return string
   */
  public static function getTemplatesDir()
  {
    return \ymF\PATH_TEMPLATES . '/' .
      Config::get('TwigService.templates_dir');
  }

  /**
   * Create Twig_Enviroment instance
   */
  protected static function _createTwig()
  {
    // Load Twig libary
    self::loadTwig();

    // Configure and create Twig

    $twig_options = Config::get('TwigService.options');

    $twig_options['cache'] = Config::get('TwigService.cache');
    
    // Cache in custom directory
    if (is_string($twig_options['cache']))
      $twig_options['cache'] = \ymF\PATH_TEMP .
        \DIRECTORY_SEPARATOR . $twig_options['cache'];

    $loader = new \Twig_Loader_Filesystem(self::getTemplatesDir());
    
    self::$twig = new \Twig_Environment($loader, $twig_options);

    // Load Escaper extension and set auto-escaping mode on
    if (Config::get('TwigService.autoescaping'))
      self::$twig->addExtension(new \Twig_Extension_Escaper(true));
  }

  /**
   * Delete Twig instance
   */
  public static function reset()
  {
    self::$twig = null;
  }

  /**
   * Prevent creation of class instance
   */
  final private function __construct() {}
}