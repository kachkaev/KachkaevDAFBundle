<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\ComponentRecords;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class CountCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:component-records:count')
            ->setDescription('Counts records in the component (all or a filtered subset)')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter (WHERE statement) to select what records to count')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $filter = $input->getOption('filter');
        $componentName = $input->getArgument('component-name');
    
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentRecordManager = $dataset->getComponentRecordManager();
    
        // Counting records to clean
        $recordCount = $componentRecordManager->count($componentName, $filter);
        $output->writeln(sprintf('The component contains %s records</info>', number_format($recordCount)));
    }
    
}