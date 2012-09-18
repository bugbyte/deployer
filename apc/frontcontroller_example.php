<?php

// first include the file containing the deployment timestamp
require '/home/bugbyte/deploy_version.php';

// then include the code that check the timestamp and performs cache clearing operations if needed
require __DIR__ .'/vendor/bugbyte/deployer/apc/handler.php';

// here comes the rest of your front controller
