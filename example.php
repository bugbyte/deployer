<?php

require_once 'lib/base/BaseDeploy.class.php';
require_once 'lib/Deploy.class.php';
require_once 'lib/exceptions/DeployException.class.php';

$deploy = new Deploy(array(
	'project_name' => 'project',
	'basedir' => dirname(__FILE__), // the root dir of the project
	'remote_host' => 'www.example.com', // can also be: array('serv1.example.com', 'serv2.example.com')
    'remote_port' => 22, // this is the default port and may be omitted
	'remote_dir' => '/home/user/project', // this is the same for all remote hosts if you define multiple
	'remote_user' => 'user', // setup public key access to make it easy for yourself, many connections are made
	'rsync_excludes' => 'config/rsync_exclude.txt',
	'data_dirs' => array( // these dirs are stored separate from the other code and replaced by symlinks
	    'web/uploads',
	    'logs'
	),
	'target_specific_files' => array( // list of files that will be renamed on the remote host
		'web/.htaccess',
		'config/database.php'
	),
	'target' => 'prod',
	'datadir_patcher'	=> 'lib/deployer/datadir-patcher.php',
));

switch($_SERVER['argv'][1]) {
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
