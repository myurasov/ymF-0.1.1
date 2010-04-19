<?php

/**
 * Singleton base class
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF;

abstract class SingletonBase
{
  protected static $instance;

  /**
   * Create class instance
   *
   * @return Singleton_Base
   */
  public static function getInstance()
  {
    if (is_null(static::$instance))
    {
      // Convert arguments to php code

      $args = func_get_args();

      for ($i = 0; $i < count($args); $i++)
      {
        $args[$i] = var_export($args[$i], true);
      }

      $args = implode(',', $args);

      // Create new class instance
      static::$instance = eval("new static($args);");
    }

    return static::$instance;
  }

  /**
   * Constructor
   */
  public function __construct()
  {
    if (!is_null(static::$instance))
    {
      throw new Exception("Class '" . get_called_class() .
        "' can be instantiated only once", ERROR_MISC);
    }
  }

  /**
   * Prevent cloning
   *
   */
  final private function __clone()
  {
  }
}