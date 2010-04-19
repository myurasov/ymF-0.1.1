<?php

namespace ymF\Response;

use ymF\Response\Renderer\RendererBase;

class HTTPResponse extends Response
{
  protected $http_headers = array();

  public function addHeader($header)
  {
    $this->http_headers[] = $header;
    return $this;
  }

  /**
   * Send representation of response
   */
  public function send(RendererBase $renderer)
  {
    for ($i = 0; $i < count($this->http_headers); $i++)
    {
      // Set header, replace existing
      header($this->http_headers[$i], true);
    }

    $renderer->display();
  }
}