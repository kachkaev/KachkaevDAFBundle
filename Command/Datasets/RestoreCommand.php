<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class RestoreCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:restore')
            ->setDescription('Restores selected dataset from a given dump file')
            ->addArgument('backup-filename', InputArgument::REQUIRED, 'Path to backup file; can be relative to point to standard backup directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $backupFilename = $input->getArgument('backup-filename');
        
        // Add standard directory to filename if it is relative
        $pathToBackup = $backupFilename;
        if (substr($backupFilename, 0, 1) !== '/' || strpos(':', $backupFilename) !== false) {
            $pathToBackup = realpath($this->getContainer()->getParameter('postgres_helper.env.datasets_backup_dir').'/'.$backupFilename);
        }
        // Extract schema name
        $schema = explode('.', basename($backupFilename))[0];
        
        $output->write(sprintf('Restoring dataset backup from <info>%s</info> (this may take a while)...', $backupFilename));
        
        $datasetManager = $this->getDatasetManager($schema);
        $datasetName = $datasetManager->restore($pathToBackup);
        
        $output->writeln(sprintf(' Done: dataset <info>%s</info> was restored.', $datasetName));
    }
}