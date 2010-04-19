<?php

/**
 * Configuration base class
 * Should be inherited only by static classes
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF;

use ymF\Exception;
use ymF\Exceptions\InternalException;

abstract class ConfigBase
{
  protected static $options = array(); // Configuration options

  /**
   * Get option / all options
   *
   * @param string $option_name
   * @return mixed
   */
  public static function get($option_name = null)
  {
    if (is_null($option_name))
    {
      return static::$options;
    }
    else
    {
      if (false !== strstr($option_name, '.')) // Sub-sectioned name
      {
        try
        {
          return self::_getSection(explode('.', $option_name), static::$options);
        }
        catch (InternalException $e)
        {
          throw new Exception("Option '$option_name' doesn't exist", \ymF\ERROR_MISC);
        }
      }
      else // Top-level name
      {
        if (array_key_exists($option_name, static::$options))
        {
          return static::$options[$option_name];
        }
        else
        {
          throw new Exception("Option '$option_name' doesn't exist", \ymF\ERROR_MISC);
        }
      }
    }
  }

  /**
   * Get value from section
   *
   * @param array $path
   * @param array $data
   * @return mixed
   */
  private static function _getSection(array $path, array $data)
  {
    // Extract current path component
    $cpath = array_shift($path);

    if (array_key_exists($cpath, $data))
    {
      // Current sub-section
      $data = $data[$cpath];
    }
    else
    {
      // Key doesn't exist
      throw new InternalException();
    }

    return empty($path) ? $data : self::_getSection($path, $data);
  }

  /**
   * Set option / multiple options
   * Option control can be added here
   *
   * @param string|array $option_name_or_array
   * @param mixed $value
   */
  public static function set($option_name_or_array, $value = null)
  {
    if (is_array($option_name_or_array)) // set([array])
    {
      if (count($option_name_or_array) > 0)
      {
        foreach ($option_name_or_array as $k => $v)
        {
          static::set($k, $v);
        }
      }
    }
    else // set(name, value)
    {
      if (false !== strstr($option_name_or_array, '.'))
      {
        self::_setSection(explode('.', $option_name_or_array), $value, static::$options);
      }
      else
      {
        static::$options[$option_name_or_array] = $value;
      }
    }
  }

  /**
   * Set section data
   *
   * @param array $path
   * @param mixed $value
   * @param array $data Data to modify
   */
  private static function _setSection(array $path, $value, array &$data)
  {
    // Extract path component
    $cpath = array_shift($path);

    // Initialize empty index
    if (!array_key_exists($cpath, $data))
      $data[$cpath] = array();

    // Reference to current section
    $cdata =& $data[$cpath];

    if (empty($path))
    {
      // Reached section, assign value
      $cdata = $value;
    }
    else
    {
      // Process next sub-section
      self::_setSection($path, $value, $cdata);
    }
  }

  /**
   * Make class static
   */
  final protected function __construct() {}
}