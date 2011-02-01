<?php

/**
* Shell script that sums up and outputs all update or rollback queries.
* Usually the output is piped into mysql directly.
* 
* @author Bert-Jan de Lange <bert-jan@bugbyte.nl>
*/

require dirname(__FILE__) .'/lib/patcher_functions.class.php';
require dirname(__FILE__) .'/lib/base/BaseDeploy.class.php';
require dirname(__FILE__) .'/lib/Deploy.class.php';
require dirname(__FILE__) .'/lib/exceptions/DeployException.class.php';
require dirname(__FILE__) .'/lib/interface/SQL_update.class.php';


if ($_SERVER['argv'][1] != 'update' && $_SERVER['argv'][1] != 'rollback')
	throw new DeployException('Update or rollback?');

if ($_SERVER['argc'] <= 2)
	throw new DeployException('Which files?');

$path = findRootPath($_SERVER['argv'][0], __FILE__);

$classes = Deploy::checkDatabaseFiles($_SERVER['argv'][1], $path, array_slice($_SERVER['argv'], 2));

echo getInstructions($_SERVER['argv'][1], $classes);

/**
 * Opens all classes
 *
 * @param string $action    update or rollback
 * @param array $classes    the names of all sql classes containing queries that need to be processed
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
