<?php

echo "Clearing APC & stat cache...\n";

// clear the apc caches so the newly deployed files are refreshed in the server's cache
apc_clear_cache();
apc_clear_cache('user');

// clear the stat cache so the changed documentroot symlink is noticed
clearstatcache();
