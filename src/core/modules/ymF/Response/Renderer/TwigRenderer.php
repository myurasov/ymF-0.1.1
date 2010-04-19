<?php

/**
 * Twig renderer
 * 
 * @package ymF
 * @copyright 2010 Misha Yurasov
 */

namespace ymF\Response\Renderer;

use ymF\Services\TwigService;
use ymF\Config;

class TwigRenderer extends RendererBase
{
  public function render()
  {
    $controller = $this->response->getController();

    // Template file name is "Controller.Method.twig"

    $template_name = $controller->getName() . '.' .
      $controller->getMethod() . '.twig';

    // If above doesn't exist, try to use name "Controller.twig"

    if (!file_exists(TwigService::getTemplatesDir() . '/' . $template_name))
      $template_name = $controller->getName() . '.twig';

    $twig = TwigService::getTwig();
    $template = $twig->loadTemplate($template_name);
    
    return $template->render($this->response->get());
  }
}