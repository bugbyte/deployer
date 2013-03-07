<?php

//if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    if (isset($_GET['rev']) && !empty($_GET['rev'])) {
        $rev = $_GET['rev'];

        if (isset($_GET['rev']) && !empty($_GET['project'])) {
            $key = $_GET['project'] .'_deploy_version';
        } else {
            // backwards compatibility
            $key = 'deploy_version';
        }

        apc_clear_cache();
        apc_clear_cache('user');
        apc_store($key, $rev);

        echo "APC VERSIONING: $key -> $rev";
    } else {
        echo apc_fetch($key);
    }
//}
