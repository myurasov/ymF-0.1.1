<?php

namespace ymF\Request;

use ArrayAccess;

class Request implements ArrayAccess
{
  protected $parameters = array();

  public function __construct(array $parameters = array())
  {
    $this->parameters = $parameters;
  }

  /**
   * Get parameter or all parameters
   *
   * @param string $parameter_name
   * @return mixed
   */
  public function get($parameter_name = null, $default = null)
  {
    if (is_null($parameter_name))
    {
      return $this->parameters;
    }
    else
    {
      if (array_key_exists($parameter_name, $this->parameters))
      {
        return $this->parameters[$parameter_name];
      }
      else
      {
        return $default;
      }
    }
  }

  /**
   * Set parameter or multiple parameters
   *
   * @param string|array $name_or_array
   * @param mixed $value
   */
  public function set($name_or_array, $value = null)
  {
    if (is_array($name_or_array)) // set([array])
    {
      $this->parameters = array_merge($this->parameters, $name_or_array);
    }
    else // set(name, value)
    {
      $this->parameters[$name_or_array] = $value;
    }
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

  // <editor-fold defaultstate="collapsed" desc="ArrayAccess implementation">

  public function offsetExists($offset)
  {
    return array_key_exists($this->parameters, $offset);
  }

  public function offsetGet($offset)
  {
    return $this->get($offset);
  }
  
  public function offsetSet($offset, $value)
  {
    $this->set($offset, $value);
  }

  public function offsetUnset($offset)
  {
    unset ($this->parameters[$offset]);
  }

  // </editor-fold>
}