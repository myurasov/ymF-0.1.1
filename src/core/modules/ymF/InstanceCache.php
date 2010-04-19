<?php

/**
 * Instance cache
 * Stores only one instance of each class
 *
 * @package ymF
 * @copyright 2010 Misha Yuarasov
 */

namespace ymF;

class InstanceCache
{
  private static $instances = array();

  /**
   * Prevent creating an instance of class
   */
  private function __construct() {}

  /**
   * Creates class instance on first use and
   * then returns it from cache
   *
   * @param string $class_name
   * @param mixed $arg1
   * @param mixed ...
   * @param mixed $argN
   * @return object
   */
  public static function get($class_name)
  {
    if (!isset(self::$instances[$class_name]))
    {
      $func_num_args = func_num_args();

      if ($func_num_args == 1)
      {
        // No constructor parameters
        self::$instances[$class_name] = new $class_name();
      }
      else
      {
        $args = func_get_args();

        if ($func_num_args == 2)
        {
          // No constructor parameters
          self::$instances[$class_name] = new $class_name($args[1]);
        }
        if ($func_num_args == 3)
        {
          // 2 constructor parameters
          self::$instances[$class_name] = new $class_name($args[1], $args[2]);
        }
        if ($func_num_args == 4)
        {
          // 3 constructor parameters
          self::$instances[$class_name] = new $class_name($args[1], $args[2], $args[3]);
        }
        else // Lots of constructor parameters
        {
          // Instantiate object with parameters

          unset($args[0]);

          for ($i = 1; $i < count($args) + 1; $i++)
          {
            $args[$i] = var_export($args[$i], true);
          }

          $args = implode(',', $args);

          eval("self::\$instances[\$class_name] = new $class_name($args);");
        }
      }
    }

    return self::$instances[$class_name];
  }

  /**
   * Reset cached class instance
   * If $class_name is NULL, cache is purged entirely
   *
   * @param string|null $class_name
   */
  public static function reset($class_name = null)
  {
    if (is_null($class_name))
    {
      self::$instances = array();
    }
    else
    {
      unset(self::$instances[$class_name]);
    }
  }
}