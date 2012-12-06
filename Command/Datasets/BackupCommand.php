<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class BackupCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:backup')
            ->setDescription('Dumps selected dataset into a backup file')
            ->makeDatasetAware()
            ->addArgument('backup-directory', InputArgument::OPTIONAL, 'Backup directory; file name will be schema.dataset-YYYY-MM-DD.pgdump')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $backupDirectory = $input->getArgument('backup-directory');
        if (!$backupDirectory) {
            $backupDirectory = $this->getContainer()->getParameter('postgres_helper.env.datasets_backup_dir');
        }
        
        $output->write(sprintf('Creating backup of <info>%s</info> (this may take a while)...', $extractedArguments['dataset-full-name']));
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $backupFilepath = $datasetManager->backup($extractedArguments['dataset-name'], $backupDirectory);
        
        $output->writeln(sprintf(' Done: backup saved to <info>%s</info>.', $backupFilepath));
    }
}