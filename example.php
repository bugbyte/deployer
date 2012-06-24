<?php

require_once 'lib/base/BaseDeploy.class.php';
require_once 'lib/Deploy.class.php';
require_once 'exceptions/DeployException.class.php';

$deploy = new Deploy(array(
	'project_name' => 'gamecowboys',
	'basedir' => dirname(__FILE__),
	'remote_host' => 'cara.bugbyte.nl', // mag ook zijn: array('cara.bugbyte.nl', 'kahlan.bugbyte.nl')
	'remote_dir' => '/home/bugbyte/gamecowboys_deploy', // bij clustering kan de dir 'clustermaster' worden gebruikt, die wordt voor nodes automatisch omgezet naar 'clusternode'
	'remote_user' => 'bugbyte',
	'rsync_excludes' => 'config/rsync_exclude.txt',
	'database_dirs' => array('plugins/dcCodebasePlugin/sql-updates', 'plugins/sfPropelActAsTaggableBehaviorPlugin/sql-updates'),
	'database_name' => 'gamecowboys',
	'database_user' => 'root',
	'target' => 'prod',
	'database-patcher'	=> 'plugins/dcCodebasePlugin/lib/deploy/database-patcher.php'
));

switch($_SERVER['argv'][1])
{
	case 'deploy':
		$deploy->deploy();
		break;
	case 'rollback':
		$deploy->rollback();
		break;
	case 'cleanup':
		$deploy->cleanup();
		break;
	default:
		echo 'Gebruik: php deploy.php [deploy|rollback|cleanup]'. PHP_EOL;
}
