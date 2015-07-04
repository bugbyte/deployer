<?php

namespace Bugbyte\Deployer\Application;

use Bugbyte\Deployer\Command\Cleanup;
use Bugbyte\Deployer\Command\Deploy;
use Bugbyte\Deployer\Command\Rollback;
use Bugbyte\Deployer\RemoteCommand\FixDataDirs;
use Bugbyte\Deployer\RemoteCommand\RestartGearmanWorkers;
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

        $this->add(new Deploy());
        $this->add(new Rollback());
        $this->add(new Cleanup());
        $this->add(new FixDataDirs());
        $this->add(new RestartGearmanWorkers());
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

}
