<?php

require_once 'lib/base/BaseDeploy.class.php';
require_once 'lib/Deploy.class.php';
require_once 'lib/exceptions/DeployException.class.php';

$deploy = new Deploy(array(
	'project_name' => 'project',
	'basedir' => dirname(__FILE__),
	'remote_host' => 'www.example.com', // can also be: array('serv1.example.com', 'serv2.example.com')
	'remote_dir' => '/home/user/project', // this is the same for all remote hosts if you define multiple
	'remote_user' => 'user',
	'rsync_excludes' => 'config/rsync_exclude.txt',
	'database_dirs' => array('data/sql-updates'),
	'database_name' => 'database',
	'database_user' => 'root',
	'target' => 'prod',
	'database-patcher'	=> 'lib/deploy/database-patcher.php'
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
		echo 'Usage: php deploy.php [deploy|rollback|cleanup]'. PHP_EOL;
}
