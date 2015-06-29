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
class Rollback extends Command
{
    protected function configure()
    {
        $this
            ->setName('deployer:rollback')
            ->setDescription('Rollback to the previous deployment')
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'The environment to roll back'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getApplication()->getConfig();

        if ($target = $input->getArgument('target')) {
            $config['target'] = $target;
        }

        $deploy = new Deployer($input, $output, $config);
        $deploy->rollback();
    }
}
