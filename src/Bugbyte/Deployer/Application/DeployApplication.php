<?php

namespace Bugbyte\Deployer\Application;

use Bugbyte\Deployer\Command\CleanupCommand;
use Bugbyte\Deployer\Command\DeployCommand;
use Bugbyte\Deployer\Command\RollbackCommand;
use Symfony\Component\Console\Application;

class DeployApplication extends Application
{
    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        parent::__construct('Bugbyte Deployer', '2.x');

        $this->config = $config;

        $this->add(new DeployCommand());
        $this->add(new RollbackCommand());
        $this->add(new CleanupCommand());
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

}
