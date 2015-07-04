<?php

namespace Bugbyte\Deployer\Command;

use Bugbyte\Deployer\Application\DeployApplication;
use Bugbyte\Deployer\Deploy\Deployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method DeployApplication getApplication()
 */
class Cleanup extends Command
{
    protected function configure()
    {
        $this
            ->setName('deployer:cleanup')
            ->setDescription('Deploys the project to a remote location')
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'The environment to clean up'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getApplication()->getConfig();

        if ($target = $input->getArgument('target')) {
            $config['target'] = $target;
        }

        $deploy = new Deployer($config);
        $deploy->cleanup();
    }
}
