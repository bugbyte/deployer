<?php

require dirname(__FILE__) .'/lib/patcher_functions.class.php';
require dirname(__FILE__) .'/lib/base/BaseDeploy.class.php';
require dirname(__FILE__) .'/lib/Deploy.class.php';
require dirname(__FILE__) .'/lib/exceptions/DeployException.class.php';
require dirname(__FILE__) .'/lib/interface/SQL_update.class.php';


if ($_SERVER['argv'][1] != 'update' && $_SERVER['argv'][1] != 'rollback')
	throw new DeployException('Update of rollback?');

if ($_SERVER['argc'] <= 2)
	throw new DeployException('Welke files?');

$path = findRootPath($_SERVER['argv'][0], __FILE__);

$classes = Deploy::checkDatabaseFiles($_SERVER['argv'][1], $path, array_slice($_SERVER['argv'], 2));

echo getInstructions($_SERVER['argv'][1], $classes);

/**
 * Opent alle classes
 *
 * @param mixed $files
 */
function getInstructions($action, $classes)
{
	$sql = '';

	foreach ($classes as $class)
	{
		if ($action == 'update')
			$sql .= $class->up() . PHP_EOL;
		elseif ($action == 'rollback')
			$sql .= $class->down() . PHP_EOL;
	}

	return $sql;
}
