<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components\Records;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class PopulateCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:components:records:populate')
            ->setDescription('Populates the component with records using a corresponding service')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentRecordManager = $dataset->getComponentRecordManager();
        
        $componentRecordManager->populate($input->getArgument('component-name'), [], $output);
    }
}