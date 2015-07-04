<?php

namespace Bugbyte\Deployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixDataDirs extends Command
{
    protected function configure()
    {
        $this
            ->setName('deployer:fixdatadirs')
            ->setDescription('Creates symlinks to the data_dirs (and moves the existing dirs if found)')
            ->addOption(
                'datadir-prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the directory that all data-dirs are placed in',
                'data'
            )
            ->addOption(
                'previous-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'The directory of the previous deployment'
            )
            ->addArgument(
                'data-dirs',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Paths of directories the website kan write to (User Generated Content)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $datadir_prefix = $input->getOption('datadir-prefix');
        $previous_dir = $input->getOption('previous-dir');
        $data_dirs = $input->getArgument('data-dirs');

        foreach ($data_dirs as $dirname) {
            $relative_path_offset = preg_replace('#[^/]+#', '..', $dirname);

            // if it's already a symlink, move on
            if (is_link($dirname)) {
                $output->writeln("$dirname is already a symlink");
                continue;
            }

            // data directories aren't supposed to be uploaded along, but if they happen to exist remove them
            if (is_dir($dirname)) {
                $output->writeln("rmdir($dirname)");
                rmdir($dirname);
            }

            // create a symlink of the datadir to the corresponding directory within the datadir-prefix
            if (!file_exists($dirname)) {
                $output->writeln("symlink($relative_path_offset/$datadir_prefix/$dirname, $dirname)");
                symlink("$relative_path_offset/$datadir_prefix/$dirname", $dirname);
            }

            // als deze directory in de vorige deployment nog wel bestond als directory dan was dat die nog niet gesplitst, dus die nu splitsen
            if ($previous_dir && is_dir("../$previous_dir/$dirname") && !is_link("../$previous_dir/$dirname")) {
                $output->writeln("rename(../$previous_dir/$dirname, ../$datadir_prefix/$dirname)\n");
                rename("../$previous_dir/$dirname", "../$datadir_prefix/$dirname");
                $output->writeln("symlink($relative_path_offset/$datadir_prefix/$dirname, ../$previous_dir/$dirname)");
                symlink("$relative_path_offset/$datadir_prefix/$dirname", "../$previous_dir/$dirname");
            }
        }
    }
}