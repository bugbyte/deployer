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
	'database-patcher'	=> 'lib/deployer/database-patcher.php',
	'datadir-patcher'	=> 'lib/deployer/datadir-patcher.php',


    // APC cache handling
    'apc_deploy_version_template' => 'lib/deployer/apc/deploy_version_template.php',
    'apc_deploy_version_path' => '/home/user/deploy_version.php',
    'apc_deploy_setrev_url' => 'localhost/deployer/apc/setrev.php'
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
