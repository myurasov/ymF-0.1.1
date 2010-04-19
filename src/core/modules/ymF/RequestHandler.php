<?php

/**
 * Request handler
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF;

use ymF\Request\HTTPRequest;
use ymF\Response\HTTPResponse;
use ymF\Controller\ControllerBase;
use ymF\Response\Renderer\RendererBase;

use ymF\Config;
use ymF\Exception;

class RequestHandler
{
  /**
   * Handle web request
   */
  public static function handleWebRequest()
  {
    // Get local configuration
    $config = Config::get('RequestHandler');

    if (isset($_GET[$config['get_args']['controller']]))
    {
      // Compose class names

      $controller_name = $_GET[$config['get_args']['controller']];

      $method_name = isset($_GET[$config['get_args']['method']]) ?
        $_GET[$config['get_args']['method']] : 'main';

      $renderer_name = isset($_GET[$config['get_args']['renderer']]) ?
          $_GET[$config['get_args']['renderer']] :  $config['default_renderer'];

      // Create request, response

      $request = new HTTPRequest();
      $response = new HTTPResponse();

      // Create controller
      
      $controller = ControllerBase::createController($controller_name, $request, $response);

      // Call controller method

      $controller->call($method_name);

      // Render and send controller response

      $renderer = RendererBase::createRenderer($renderer_name, $response);
      $response->send($renderer);
    }
    else
    {
      throw new Exception('Bad call', \ymF\ERROR_MISC);
    }
  }
}