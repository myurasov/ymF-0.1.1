<?php

/**
 * Plain text renderer
 */

namespace ymF\Response\Renderer;

class TextRenderer extends RendererBase
{
  public function render()
  {
    $data = $this->response->get();
    return print_r($data, true);
  }
}