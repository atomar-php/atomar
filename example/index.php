<?php

date_default_timezone_set('America/Los_Angeles');

require_once('.atomar/Atomar.php');

/**
 * Initializes the system.
 * Parameters may be passed directly as a map or you may specify a configuration file
 * @param $config_path mixed the path to the site configuration or a map of values
 * @throws \Exception
 */
\Atomar\Atomar::init('config.json');

/**
 * Starts up the system!
 * You may optionally specify what app directory to run
 * otherwise the one specified in the config will be used.
 * @param string $app_path An optional path to the application directory
 * @throws \Exception
 */
\Atomar\Atomar::run();