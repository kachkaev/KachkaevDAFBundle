<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components\Records;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class CleanCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:records:clean')
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
        $componentRecordManager = $dataset->getComponentRecordManager();
    
        // Counting records to clean
        $recordCount = $componentRecordManager->count($componentName, $filter);
        if ($recordCount > 0) {
            if ($this->forceNotUsed($input, $output, sprintf('%s records in component %s of the dataset %s will be lost!', number_format($recordCount), $componentName, $dataset->getFullName()))) {
                return 1;
            } else {
                $output->write(sprintf('Deleting %s records in component %s of the dataset %s...', number_format($recordCount), $componentName, $dataset->getFullName()));
                $componentRecordManager->clean($componentName, $filter);
                $output->writeln(' Done.');
            }
        } else {
                $output->writeln(sprintf('<comment>0 records to delete in component %s of the dataset %s%s.</comment>', $componentName, $dataset->getFullName(), $filter !== null ? ' with the given filter' : ''));
        }
    }
}