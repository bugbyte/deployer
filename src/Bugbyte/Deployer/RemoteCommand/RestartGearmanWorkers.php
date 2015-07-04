<?php

namespace Bugbyte\Deployer\RemoteCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestartGearmanWorkers extends Command
{
    protected function configure()
    {
        $this
            ->setName('deployer:restartgearmanworkers')
            ->setDescription('Restarts gearman workers by sending them the "reboot" command')
            ->addOption(
                'ip',
                null,
                InputOption::VALUE_REQUIRED,
                'The ip address of gearmand',
                '127.0.0.1'
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_REQUIRED,
                'The port of gearmand',
                4730
            )
            ->addArgument(
                'function',
                InputArgument::REQUIRED,
                'The function name of the worker to restart'
            )
            ->addArgument(
                'workload',
                InputArgument::OPTIONAL,
                'The workload to send the worker to tell it to restart',
                'reboot'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ip = $input->getOption('ip');
        $port = $input->getOption('port');
        $workload = $input->getArgument('workload');
        $function = $input->getArgument('function');

        $output->write("Sending workload \"$workload\" to function \"$function\" at $ip:$port .. ");

        $client = new \GearmanClient();
        $client->addServer($ip, $port);
        $client->doBackground($function, $workload);

        $output->writeln("done");
    }
}