<?php

/**
 * Project bootstrap file
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

// Remove execution timeout
set_time_limit(0);

// Constants
const PROJECT_NAME = 'TestApp';

// Include ymF
require __DIR__ . '/ymF/ymF.php';

// Register project namespace for autoload
ymF\Kernel::registerAutoloadNamespace(PROJECT_NAME);