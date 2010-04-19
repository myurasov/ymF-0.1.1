<?php

namespace ymF\Response;

use ArrayAccess;

use ymF\Exception;
use ymF\Response\Renderer\RendererBase;
use ymF\Controller\ControllerBase;

class Response implements ArrayAccess
{
  protected $data = array();

  /**
   * @var ControllerBase
   */
  protected $controller;

  /**
   * Get field or all data fields
   *
   * @param string $data_field_name
   * @return mixed
   */
  public function get($data_field_name = null, $default = null)
  {
    if (is_null($data_field_name))
    {
      return $this->data;
    }
    else
    {
      if (array_key_exists($data_field_name, $this->data))
      {
        return $this->data[$data_field_name];
      }
      else
      {
        throw new Exception("Data field '$data_field_name' is not defined", ERROR_MISC);
      }
    }
  }

  /**
   * Set data field or multiple fileds
   *
   * @param string|array $name_or_array
   * @param mixed $value
   */
  public function set($name_or_array, $value = null)
  {
    if (is_array($name_or_array)) // set([array])
    {
      $this->data = array_merge($this->data, $name_or_array);
    }
    else // set(name, value)
    {
      $this->data[$name_or_array] = $value;
    }

    return $this;
  }

  /**
   * Render response
   *
   * @param RendererBase $renderer
   */
  public function render(RendererBase $renderer)
  {
    return $renderer->render();
  }

  /**
   * Associate controller with response
   * 
   * @param ControllerBase $controller
   */
  public function setController(ControllerBase $controller)
  {
    $this->controller = $controller;
  }

  /**
   * Get associated controller
   *
   * @return ControllerBase
   */
  public function getController()
  {
    return $this->controller;
  }

  // <editor-fold defaultstate="collapsed" desc="ArrayAccess implementation">

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
    $this->set($offset, $value);
  }

  public function offsetUnset($offset)
  {
    unset ($this->data[$offset]);
  }

  // </editor-fold>
}