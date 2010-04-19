<?php

namespace ymF\Request;

class HTTPRequest extends Request
{
  public function __construct()
  {
    $session = isset($_SESSION) ? $_SESSION : array();

    $this->parameters = array_merge(
      $session,
      $_FILES,
      $_COOKIE,
      $_POST,
      $_GET
    );
  }
}