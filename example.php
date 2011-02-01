<?php

/**
* An example of a simpel shell script to demo the usage of the deployer
* 
* @author Bert-Jan de Lange <bert-jan@bugbyte.nl>
*/

require_once 'lib/base/BaseDeploy.class.php';
require_once 'lib/Deploy.class.php';
require_once 'exceptions/DeployException.class.php';

$deploy = new Deploy(array(
	'project_name' => 'my-dummy-project',
	'basedir' => dirname(__FILE__),
	'remote_host' => 'dummy-server.com', // can also be an array of hostnames
	'remote_dir' => '/home/dummyuser/my-dummy-project',
	'remote_user' => 'dummyuser',
	'rsync_excludes' => 'config/rsync_exclude.txt',
	'database_dirs' => array('config/sql-updates', 'plugins/sfPropelActAsTaggableBehaviorPlugin/sql-updates'),
	'database_name' => 'my-dummy-database',
	'database_user' => 'root',
	'target' => 'prod',
	'database-patcher'	=> 'lib/deploy/database-patcher.php'
));

switch($_SERVER['argv'][1])
{
	case 'deploy':
		$deploy->check();
		break;
	case 'rollback':
		$deploy->rollback();
		break;
	default:
		echo 'Usage: php deploy.php [check|deploy|rollback]'. PHP_EOL;
}
