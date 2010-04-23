<?php

require __DIR__ . '/../src/core/modules/Project.php';

use ymF\CLIUtil;

$u = new CLIUtil(array(
    'script_name' => 'ymF installer',
    'script_version' => \ymf\VERSION,
    'script_description' => "Creates ymF-based project",
    'verbocity_default' => CLIUtil::MESSAGE_ERROR .
      CLIUtil::MESSAGE_INFORMATION .
      CLIUtil::MESSAGE_STATUS,
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

//

$params = $u->getParameters();

//

flush();

$u->start();

$u->status("Creating project in '{$params['d']}'...");

// Copy files
$__base_dir = realpath(__DIR__ . '/../src');
recurse_dir($__base_dir, '__copy_files', true, true);

// Replace in files
__replace_in_files(array(
  'core/modules/Project.php',
  'core/configs/TestApp.Config.php',
  'core/modules/TestApp/Controllers/Hello.php',
));

// Rename files
__rename(array('core/modules/TestApp', 'core/configs/TestApp.Config.php'));

$u->end();

//

// <editor-fold defaultstate="collapsed" desc="Funtions">

/**
 * Calls callback function for each item in the directory
 *
 * @param str $basedir Directory to scan
 * @param str $callback Funtion that is called for each found item with it's path.
 * Callback function should return TRUE if scan should be continued.
 * @param bool $dirs Call callback function with dirs
 * @param bool $recursive Scan sub-directories recursive?
 */
function recurse_dir($basedir, $callback, $dirs = true, $recursive = true)
{
  if (is_dir($basedir))
  {
    if ($dh = opendir($basedir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if (($file != '.') and ($file != '..'))
        {
          $curpath  = $basedir . \DIRECTORY_SEPARATOR . $file;
          $_is_dir  = is_dir($curpath);

          if ($recursive && $_is_dir)
          {
            if (!recurse_dir($curpath, $callback, $dirs, $recursive))
              return false;
          }

          if (!$_is_dir || $dirs)
          {
            if (!$callback($curpath))
              return false;
          }
        }
      }
      closedir($dh);
    }
  }

  return true;
}

function __copy_files($path_src)
{
  global $__base_dir, $params, $u;

  $path_rel = substr($path_src, strlen($__base_dir) + 1);
  $path_dst = $params['d'] . DIRECTORY_SEPARATOR . $path_rel;

  if (is_dir($path_src))
  {
    if (is_dir($path_dst) || @mkdir($path_dst, null, true))
    {
      $u->info("Created directory '$path_dst'");
    }
    else
    {
      $u->error("Error creating directory '$path_dst'");
    }
  }
  else
  {
    $path_dst_dir = dirname($path_dst);

    if (!is_dir($path_dst_dir))
    {
      if (@mkdir($path_dst_dir, null, true))
      {
        $u->info("Created directory '$path_dst_dir'");
      }
      else
      {
        $u->error("Error creating directory '$path_dst_dir'");
      }
    }

    if (@copy($path_src, $path_dst))
    {
      $u->info("Copied file '$path_rel'");
    }
    else
    {
      $u->error("Error copying file '$path_rel'");
    }
  }

  return true;
}

function __rename(array $paths_relative)
{
  global $u, $params;

  foreach ($paths_relative as $path_relative)
  {
    $path_relative_dst = str_replace('TestApp', $params['p'], $path_relative);
    $path_src = $params['d'] . DIRECTORY_SEPARATOR . $path_relative;
    $path_dst = $params['d'] . DIRECTORY_SEPARATOR . $path_relative_dst;

    if (@rename($path_src, $path_dst))
    {
      $u->info("Renamed '$path_relative' to '$path_relative_dst'");
    }
    else
    {
      $u->info("Error renaming '$path_relative' to '$path_relative_dst'");
    }
  }
}

function __replace_in_files(array $files)
{
  global $u, $params;

  foreach ($files as $file)
  {
    $path = $params['d'] . '/' . $file;
    $data = file_get_contents($path);
    $data = str_replace('TestApp', $params['p'], $data);
    file_put_contents($path, $data);
    $u->info("Project name in '$file' changed");
  }
}

// </editor-fold>

?>