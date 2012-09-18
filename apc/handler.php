<?php

// checks deploy version against the one in APC's cache and resets the cache if they don't match
$deployment_key = 'deploy_version';

if (($rev = apc_fetch($deployment_key)) != DEPLOY_VERSION) {
    apc_clear_cache();
    apc_clear_cache('user');

    if ($rev < DEPLOY_VERSION)
        apc_store($deployment_key, DEPLOY_VERSION);
}

$deployment_pid_key = 'php.pid_'.getmypid();

if (($rev = apc_fetch($deployment_pid_key)) != DEPLOY_VERSION) {
    if ($rev < DEPLOY_VERSION)
        apc_store($deployment_pid_key, DEPLOY_VERSION);

    clearstatcache(true);
}
