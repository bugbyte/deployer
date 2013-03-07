<?php

// first include the file containing the deployment timestamp (by default it's one directory above in the project root)
require __DIR__ .'/../../deploy_version.php';

// then include the code that check the timestamp and performs cache clearing operations if needed
require __DIR__ .'/vendor/bugbyte/deployer/apc/handler.php';

// here comes the rest of your front controller
