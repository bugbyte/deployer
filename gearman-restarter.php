<?php

require dirname(__FILE__) .'/lib/patcher_functions.class.php';
require dirname(__FILE__) .'/lib/base/BaseDeploy.class.php';
require dirname(__FILE__) .'/lib/Deploy.class.php';
require dirname(__FILE__) .'/lib/exceptions/DeployException.class.php';

$args = parseArgs($_SERVER['argv']);

//gearman client starten
$client = new GearmanClient();
$client->addServer($args['ip'], $args['port']);

//worker reboot job starten
$client->doBackground($args['function'], 'reboot');
