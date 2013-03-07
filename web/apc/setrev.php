<?php

//if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    if (!empty($_GET['rev'])) {
        if (!empty($_GET['project'])) {
            $key = $_GET['project'] .'_deploy_version';
            $rev = $_GET['rev'];
        } else {
            // backward compatibility
            $key = 'deploy_version';
            $rev = $_GET['rev'];
        }

        apc_clear_cache();
        apc_clear_cache('user');
        apc_store($key, $rev);

        echo "APC VERSIONING: $key -> $rev";
    } else {
        echo apc_fetch($key);
    }
//}
