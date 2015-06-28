<?php

namespace Bugbyte\Deployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        parent::__construct('deploy');
    }

    protected function configure()
    {
        $this
            ->setDescription('Deploys the project to a remote location')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Do you want to [deploy|rollback|check]'
            )
            ->addArgument(
                'env',
                InputArgument::OPTIONAL,
                'Which environment do you want to deploy to ?'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
//        if (!preg_match('/^(deploy|rollback|cleanup)$/', $input->getArgument('action'))) {
//            throw new \InvalidArgumentException('action must be one of deploy, rollback, clean');
//        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        var_dump($input->getArguments());
        var_dump($input->getArgument('action'));

        $method = $input->getArgument('action');

        $deploy = new \Deploy($this->config);
        $deploy->$method();
    }
}
