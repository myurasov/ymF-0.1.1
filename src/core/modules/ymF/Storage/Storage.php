<?php

/**
 * Class for adding options to other classes
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF\Storage;

use ArrayAccess;
use ymF\Exception;

class Storage implements ArrayAccess
{
  /**
   * Data fields
   *
   * @var array
   */
  protected $data = array();

  /**
   * Watching object
   *
   * @var HostInterface
   */
  protected $host = null;

  /**
   * Constructor
   *
   * @param IHost $host Host object
   * @param array $default_data Default options array
   */
  public function __construct(array $default_data = array())
  {
    $this->data = $default_data;
  }

  /**
   * Set host object
   * 
   * @param HostInterface $host
   * @return Storage
   */
  public function setHost(HostInterface $host)
  {
    $this->host = $host;
    
    return $this;
  }

  /**
   * Get field or all fields
   *
   * @param string $field_name
   * @return mixed
   */
  public function get($field_name = null)
  {
    if (is_null($field_name))
    {
      return $this->data;
    }
    else
    {
      if (array_key_exists($field_name, $this->data))
      {
        return $this->data[$field_name];
      }
      else
      {
        throw new Exception("Data field '$field_name' doesn't exist", \ymF\ERROR_MISC);
      }
    }
  }

  /**
   * Set field or multiple fields
   *
   * @param string|array $name
   * @param mixed $value
   * @return Storage
   */
  public function set($name, $value = null)
  {
    if (is_array($name)) // set([array])
    {
      if (count($name) > 0)
      {
        foreach ($name as $k => $v)
        {
          $this->set($k, $v);
        }
      }
    }
    else // set(name, value)
    {
      if (array_key_exists($name, $this->data))
      {
        if (is_null($this->host) || $this->_validateField($name, $value))
        {
          $this->data[$name] = $value;
        }
      }
      else
      {
        throw new Exception("Data field '$name' doesn't exist", \ymF\ERROR_MISC);
      }
    }

    return $this;
  }

  /**
   * Default property getter
   *
   * @param string $name
   * @return mixed
   */
  public function __get($name)
  {
    return $this->get($name);
  }

  /**
   * Default setter
   *
   * @param string $name
   * @param mixed $value
   * @return Storage
   */
  public function __set($name, $value)
  {
    return $this->set($name, $value);
  }

  // <editor-fold defaultstate="collapsed" desc="Implementation of ArrayAcces interface">

  public function offsetExists($offset)
  {
    return array_key_exists($this->data, $offset);
  }

  public function offsetGet($offset)
  {
    return $this->get($offset);
  }

  public function offsetSet($offset, $value)
  {
    if ($offset === null)
    {
      throw new Exception('Option name not specified', ERROR_MISC);
    }
    else
    {
      $this->set($offset, $value);
    }
  }

  public function offsetUnset($offset)
  {
    throw new Exception("Can't unset option '$offset'", ERROR_MISC);
  }

  // </editor-fold>

  /**
   * Ask host about field change
   */
  private function _validateField($name, $value)
  {
    if ($this->host !== null)
    {
      // Ask host
      return $this->host->validateField($this, $name, $value);
    }
    else
    {
      // No host is set
      return true;
    }
  }
}