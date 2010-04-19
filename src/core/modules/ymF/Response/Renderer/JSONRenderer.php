<?php

/**
 * JSON renderer
 */

namespace ymF\Response\Renderer;

class JSONRenderer extends RendererBase
{
  public function render()
  {
    $data = $this->response->get();
    return json_encode($data);
  }
}