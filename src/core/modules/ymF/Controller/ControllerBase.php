<?php

/**
 * Controller base
 *
 * "Controller" is an object, accessible from outside
 *
 * @package ymF
 * @copyright 2010 Misha Yurasov
 */

namespace ymF\Controller;

use ymF\Request\Request;
use ymF\Response\Response;
use ymF\Exception;
use ymF\Config;

class ControllerBase
{
  /**
   * @var Request
   * @property-read $request
   */
  protected $request;

  /**
   * @var Response
   * @property-read $response
   */
  protected $response;

  /**
   * Last method called
   */
  private $method = '';

  public function getMethod()
  {
    return $this->method;
  }

  /**
   * Controller name
   */
  protected $name = '';

  public function getName()
  {
    return $this->name;
  }

  /**
   * List of methods that can't be called from outside
   *
   * @var array
   */
  private $restricted_methods = array(
    '__construct',
    '__get',
    'call'
  );

  /**
   * Contructor
   *
   * @param Request $request
   * @param Response $response
   */
  public function __construct(Request $request, Response $response)
  {
    $this->request = $request;
    $this->response = $response;

    // Get class name relative to controller namespace
    // and use it as controller name

    if ($this->name == '')
    {
      $this->name = get_called_class();
      $controllers_namespace = Config::get('project_name') . '\\' .
        Config::get('controllers_namespace');
      $this->name = substr($this->name, strlen($controllers_namespace) + 1);
    }
  }

  /**
   * Property get
   *
   * @param string $property_name
   * @return mixed
   */
  public function __get($property_name)
  {
    switch ($property_name)
    {
      case 'request':
      case 'response':
        return $this->$property_name;
        break;

      default:
        throw new Exception("Unknown property '" .
          get_called_class() . "::$property_name'", \ymF\ERROR_MISC);
        break;
    }
  }

  /**
   * Dispatch method call
   *
   * @param string $method
   * @return Response
   */
  public function call($method = 'main')
  {
    if (!in_array(strtolower($method), $this->restricted_methods))
    {
      if (method_exists($this, $method))
      {
        // Save method name
        $this->method = $method;

        // Set controller
        $this->response->setController($this);

        // Execute method
        $result = $this->$method();

        // Save results to response
        if (!is_null($result))
          $this->response->set($method, $result);

        // Return response object
        return $this->response;
      }
      else
      {
        throw new Exception("Method '" . get_called_class() .
          "::$method()' doesn't exist", \ymF\ERROR_MISC);
      }
    }
    else
    {
      throw new Exception("Access to method '$method()' is restricted",
      \ymF\ERROR_ACCESS_DENIED);
    }
  }

  /**
   * Controllers factory
   * 
   * @param string $name
   * @param Request $request
   * @param Response $response
   * @return ControllerBase
   */
  public static function createController($name, Request $request, Response $response)
  {
    $name = Config::get('project_name') . '\\' .
      Config::get('controllers_namespace') . '\\' . $name;
    
    return new $name($request, $response);
  }
}