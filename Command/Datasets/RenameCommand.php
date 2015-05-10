<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class RenameCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:rename')
            ->setDescription('Renames given dataset')
            ->makeDatasetAware()
            ->addArgument('dataset-new-name', InputArgument::REQUIRED, 'New name of the dataset (without schema)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $output->write(sprintf('Renaming dataset <info>%s.%s</info> to <info>%s</info>...', $extractedArguments['dataset-schema'], $extractedArguments['dataset-name'], $input->getArgument("dataset-new-name")));
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $datasetManager->rename($extractedArguments['dataset-name'], $input->getArgument("dataset-new-name"));

        $output->writeln(' Done.');
    }
}