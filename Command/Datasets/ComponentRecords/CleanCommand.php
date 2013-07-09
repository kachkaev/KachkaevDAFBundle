<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\ComponentRecords;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class CleanCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:component-records:clean')
            ->setDescription('Removes records from the component (all or a filtered subset)')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter (WHERE statement) to select what records to delete')
            ->makeForceAware()
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $filter = $input->getOption('filter');
        $componentName = $input->getArgument('component-name');
    
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentDataManager = $dataset->getComponentRecordManager();
    
        // Counting records to clean
        $recordCount = $componentDataManager->count($componentName, $filter);
        if ($recordCount > 0) {
            if ($this->forceNotUsed($input, $output, sprintf('%d records in component %s of the dataset %s will be lost!', $recordCount, $componentName, $dataset->getFullName()))) {
                return 1;
            } else {
                $output->write(sprintf('Deleting %d records in component %s of the dataset %s...', $recordCount, $componentName, $dataset->getFullName()));
                $componentDataManager->clean($componentName, $filter);
                $output->writeln(' Done.');
            }
        } else {
                $output->writeln(sprintf('<comment>0 records to delete in component %s of the dataset %s%s.</comment>', $componentName, $dataset->getFullName(), $filter !== null ? ' with the given filter' : ''));
        }
    }
    
}