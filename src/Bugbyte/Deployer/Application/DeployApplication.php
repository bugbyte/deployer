<?php

namespace Bugbyte\Deployer\Application;

use Bugbyte\Deployer\Command\DeployCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

class DeployApplication extends Application
{
    public function __construct($config)
    {
        parent::__construct();

        $this->add(new DeployCommand($config));
    }

    protected function getCommandName(InputInterface $input)
    {
        return 'deploy';
    }
}