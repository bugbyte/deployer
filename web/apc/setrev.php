<?php

//if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    $key = 'deploy_version';

    if (!empty($_GET['rev'])) {
        $rev = (isset($_GET['rev'])) ? (int)$_GET['rev'] : '';
        apc_clear_cache();
        apc_clear_cache('user');
        apc_store($key, $rev);

        echo "APC VERSIONING: $key -> $rev";
    } else {
        echo apc_fetch($key);
    }
//}
