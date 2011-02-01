<?php

/**
* Shell script that verifies all datadirs have been moved out of the project code tree and are replaced by symlinks.
* 
* @author Bert-Jan de Lange <bert-jan@bugbyte.nl>
*/

require dirname(__FILE__) .'/lib/patcher_functions.class.php';
require dirname(__FILE__) .'/lib/base/BaseDeploy.class.php';
require dirname(__FILE__) .'/lib/Deploy.class.php';
require dirname(__FILE__) .'/lib/exceptions/DeployException.class.php';

if ($_SERVER['argc'] <= 2)
	throw new DeployException('Which directories?');

$path = findRootPath($_SERVER['argv'][0], __FILE__);

$args = parseArgs($_SERVER['argv']);

$datadir_prefix = $args['datadir-prefix'];
$previous_dir = $args['previous-dir'];
unset($args['datadir-prefix'], $args['target-dir'], $args['previous-dir']);

foreach ($args as $dirname)
{
	$relative_path_offset = preg_replace('#[^/]+#', '..', $dirname);

	// datadir should not have been uploaded. If they exists anyway, remove them
	if (is_dir($dirname) && !is_link($dirname))
	{
		echo "rmdir($dirname)\n";
		rmdir($dirname);
	}
	// if the directory is already a symlink, leave it alone
	elseif (is_link($dirname))
	{
		echo "$dirname is already a symlink\n";
		continue;
	}

	// if the target directory doesn't exist, a symlink can be created to it's corresponding datadir
	if (!file_exists($dirname))
	{
		echo "symlink($relative_path_offset/$datadir_prefix/$dirname, $dirname)\n";
		symlink("$relative_path_offset/$datadir_prefix/$dirname", $dirname);
	}

	// if this directory was still a directory in the previous deployment it hadn't been split up yet. Let's do that now.
	if ($previous_dir && is_dir("../$previous_dir/$dirname") && !is_link("../$previous_dir/$dirname"))
	{
		echo "rename(../$previous_dir/$dirname, ../$datadir_prefix/$dirname)\n";
		rename("../$previous_dir/$dirname", "../$datadir_prefix/$dirname");
		echo "symlink($relative_path_offset/$datadir_prefix/$dirname, ../$previous_dir/$dirname)\n";
		symlink("$relative_path_offset/$datadir_prefix/$dirname", "../$previous_dir/$dirname");
	}
}
