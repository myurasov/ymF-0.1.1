<?php

/**
 * Doctrine service
 *
 * @uses Doctrine 1.2
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF\Services;

use ymF\Kernel;

class DoctrineService
{
  /**
   * Load Doctrine and register autoloader
   */
  public static function loadDoctrine()
  {
    if (!class_exists('Doctrine', false))
    {
      require Kernel::getLibraryPath('Doctrine') . '/Doctrine.php';
      spl_autoload_register(array('Doctrine', 'autoload'));
    }
  }
}