<?php

require __DIR__ . '/../src/core/modules/Project.php';

use ymF\ConsoleUtil;

$u = new ConsoleUtil(array(
    'script_name' => 'ymF installer',
    'script_version' => \ymf\VERSION,
    'script_description' => "Creates ymF-based project",
    'verbocity_default' => ConsoleUtil::MESSAGE_ERROR .
      ConsoleUtil::MESSAGE_INFORMATION .
      ConsoleUtil::MESSAGE_STATUS,
    'status_start_message' => null,
    'status_end_message' => null
));

$u->declareParameter('project_name', 'p', 'NewProject', null, 'Project name');

$u->declareParameter('destination', 'd', __DIR__ .
  DIRECTORY_SEPARATOR . 'NewProject', null, 'Destination directory');

if ($u->getParameter('?'))
{
  $u->displayHelp();
  exit;
}