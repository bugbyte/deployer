<?php

namespace Bugbyte\Deployer\Command;

use Bugbyte\Deployer\Application\DeployApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method DeployApplication getApplication()
 */
class RollbackCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rollback')
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

        $deploy = new \Deploy($config);
        $deploy->rollback();
    }
}
