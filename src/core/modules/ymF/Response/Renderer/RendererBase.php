<?php

/**
 * Response renderer base class
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF\Response\Renderer;

use ymF\Controller\ControllerBase;
use ymF\Response\Response;

abstract class RendererBase
{
  /**
   * @var Response
   */
  protected $response;

  /**
   * Constructor
   * 
   * @param Response $response
   */
  public function __construct(Response $response)
  {
    $this->response = $response;
  }

  /**
   * Render response
   *
   * @return string
   */
  abstract public function render();

  public function display()
  {
    echo $this->render();
  }

  public function setResponse(Response $response)
  {
    $this->response = $response;
  }

  /**
   * Get response object
   *
   * @return Response
   */
  public function getResponse()
  {
    return $this->response;
  }

  /**
   * Renderers factory
   *
   * @param string $name
   * @return RendererBase
   */
  public static function createRenderer($name, Response $response)
  {
    $name = 'ymF\\Response\\Renderer\\' . $name . 'Renderer';
    return new $name($response);
  }
}