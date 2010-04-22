<?php

namespace ymF;

require __DIR__ . '/../../core/modules/Project.php';

use ymF\CLIUtil;
use ymF\Config;
use ymF\Request\Request;
use ymF\Response\Response;
use ymF\Response\Renderer\RendererBase;
use ymF\Controller\ControllerBase;

$u = new CLIUtil(array(
    'script_name' => 'Controller call',
    'script_version' => '1.0',
    'script_description' => 'Calls controller'
));

$u->declareParameter('controller', 'c', '', null, 'Controller name');
$u->declareParameter('method', 'm', 'main', null, 'Method name');
$u->declareParameter('renderer', 'r', 'Twig', null, 'Renderer name');
$u->declareParameter('query', 'q', '', null, 'Query string');

if ($u->getParameter('?'))
{
  $u->displayHelp();
  exit;
}
else
{
  $controller = $u->getParameter('controller');
  $method = $u->getParameter('method');
  $renderer = $u->getParameter('renderer');
  $query = $u->getParameter('query');

  parse_str($query, $query);

  $request = new Request($query);
  $response = new Response();
  $controller = ControllerBase::createController($controller, $request, $response);
  $renderer = RendererBase::createRenderer($renderer, $response);

  // Call controller method
  echo $controller->call($method)->render($renderer);
}