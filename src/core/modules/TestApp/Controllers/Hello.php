<?php

namespace TestApp\Controllers;

use ymF\Controller\ControllerBase;

class Hello extends ControllerBase
{
  protected function main()
  {
    return "Hello from " . get_called_class() . "!";
  }
}