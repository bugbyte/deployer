<?php

// checks deploy version against the one in APC's cache and resets the cache if they don't match
if (defined('DEPLOY_PROJECT')) {
    $deployment_key = DEPLOY_PROJECT .'_deploy_version';
    $deployment_pid_key = DEPLOY_PROJECT .'_stat_'. getmypid();
} else {
    $deployment_key = 'deploy_version';
    $deployment_pid_key = 'php.pid_'.getmypid();
}

$rev = apc_fetch($deployment_key);

if ($rev === false) {
    // the key doesn't exist, so this project is new and no clear cache is needed
    apc_store($deployment_key, DEPLOY_VERSION);
} elseif ($rev != DEPLOY_VERSION) {
    // the key exists but it's the wrong value, clear the cache
    apc_clear_cache();
    apc_clear_cache('user');
    apc_store($deployment_key, DEPLOY_VERSION);
}

// do the same check for every thread of the server (apache, php-fpm, etc.)
if (apc_fetch($deployment_pid_key) != DEPLOY_VERSION) {
    clearstatcache(true);

    apc_store($deployment_pid_key, DEPLOY_VERSION, 3600);
}
