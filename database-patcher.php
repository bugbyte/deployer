<?php

require dirname(__FILE__) .'/lib/patcher_functions.class.php';
require dirname(__FILE__) .'/lib/base/BaseDeploy.class.php';
require dirname(__FILE__) .'/lib/Deploy.class.php';
require dirname(__FILE__) .'/lib/exceptions/DeployException.class.php';
require dirname(__FILE__) .'/lib/interface/SQL_update.class.php';

if ($_SERVER['argv'][1] == 'update' || $_SERVER['argv'][1] == 'rollback') {
    $action = $_SERVER['argv'][1];
} else {
    throw new DeployException('Update of rollback?');
}

if (isset($_SERVER['argv'][2])) {
    $database = $_SERVER['argv'][2];
} else {
    throw new DeployException('Welke database?');
}

if ($_SERVER['argc'] > 3) {
    $patches = array_slice($_SERVER['argv'], 3);
} else {
    throw new DeployException('Welke files?');
}

$path = findRootPath($_SERVER['argv'][0], __FILE__);

$classes = Deploy::checkDatabaseFiles($action, $path, $patches);

echo getInstructions($action, $classes);

/**
 * Opent alle classes
 *
 * @param string $action
 * @param SQL_update[] $classes
 * @return string
 */
function getInstructions($action, $classes)
{
    $sql = '';

    foreach ($classes as $class) {
        if ($action == 'update')
            $sql .= $class->up() . PHP_EOL;
        elseif ($action == 'rollback')
            $sql .= $class->down() . PHP_EOL;
    }

    return $sql;
}
