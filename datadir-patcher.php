<?php

use Bugbyte\Deployer\Exception\DeployException;

require dirname(__FILE__) . '/lib/patcher_functions.php';
require dirname(__FILE__) . '/src/Bugbyte/Deployer/Exception/DeployException.php';

if ($_SERVER['argc'] <= 2) {
    throw new DeployException('Welke dirs?');
}

$path = findRootPath($_SERVER['argv'][0], __FILE__);

$args = parseArgs($_SERVER['argv']);

$datadir_prefix = $args['datadir-prefix'];
$previous_dir = $args['previous-dir'];
unset($args['datadir-prefix'], $args['target-dir'], $args['previous-dir']);

foreach ($args as $dirname) {
    $relative_path_offset = preg_replace('#[^/]+#', '..', $dirname);

    // datadirectories horen niet mee te komen met de upload, maar als ze er toch staan.. byebye
    if (is_dir($dirname) && !is_link($dirname)) {
        echo "rmdir($dirname)\n";
        rmdir($dirname);
    }
    // als het al een symlink is, met rust laten
    elseif (is_link($dirname)) {
        echo "$dirname is al een symlink\n";
        continue;
    }

    // als er niets staat op de doellocatie kan er een symlink worden gemaakt naar dezelfde locatie binnen de datadir
    if (!file_exists($dirname)) {
        echo "symlink($relative_path_offset/$datadir_prefix/$dirname, $dirname)\n";
        symlink("$relative_path_offset/$datadir_prefix/$dirname", $dirname);
    }

    // als deze directory in de vorige deployment nog wel bestond als directory dan was dat die nog niet gesplitst, dus die nu splitsen
    if ($previous_dir && is_dir("../$previous_dir/$dirname") && !is_link("../$previous_dir/$dirname")) {
        echo "rename(../$previous_dir/$dirname, ../$datadir_prefix/$dirname)\n";
        rename("../$previous_dir/$dirname", "../$datadir_prefix/$dirname");
        echo "symlink($relative_path_offset/$datadir_prefix/$dirname, ../$previous_dir/$dirname)\n";
        symlink("$relative_path_offset/$datadir_prefix/$dirname", "../$previous_dir/$dirname");
    }
}
