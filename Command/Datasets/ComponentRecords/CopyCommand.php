<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\ComponentRecords;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class CopyCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:component-data:aggregate')
            ->setDescription('Aggregates data using a corresponding service and saves it into the db')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('dataset-from-name', InputArgument::REQUIRED, 'Name of the dataset within the same schema to copy data from')
            ->markAsStub()
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        //$datasetType = $dataset->getProperty('type');
        
        // The dataset must have the component defined
        $dataset->getComponentManager()->assertHaving('items', 'The dataset is missing the ‘items’ component');
        
        // The dataset must have type defined
        $dataset->assertHavingProperty('type');
        
        // Looking for a collector according to the dataset type
        
        /** @var AbstractItemsCollector
         */
        $itemsCollector = null;
        
        try {
            $itemsCollector = $this->getContainer()->get(sprintf('pr.datasets.items.collector.%s', $dataset->getProperty('type')));
        } catch (InvalidArgumentException $e) {
            throw new \LogicException(sprintf('Dont’t know how to collect items for a dataset of type "%s"', $dataset->getProperty('type')));
        }
        
        $itemsCollector->collect($dataset, $output);
        //$datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        //$size = $input->getArgument('size');
        
        // Get the appropriate collector (according to the dataset type)
        $this->processInput($input, $output);
        
        //$this->getContainer()->get('')
    }
}