<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\ComponentRecords;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class CopyCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:component-records:copy')
            ->setDescription('Copies records into the component from another dataset')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('origin-dataset-name', InputArgument::REQUIRED, 'Name of the dataset within the same schema to copy data from')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter (WHERE statement) to select what records to copy')
            ->addOption('existing-only', null, InputOption::VALUE_NONE, 'Only update attribute values of the records that already exist in the destination dataset component')
            ->addOption('ignore-structure-difference', null, InputOption::VALUE_NONE, 'Does not throw an error when there are mismatches in attributes (columns) between the datasets')
            ->addOption('attribute-mappings', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Comma-separated array of attribute (column) names that need to be renamed / casted, e.g. "myfield->myfield_with_new_name,myotherfield::int=>myotherfield_of_new_type"')
            ->markAsStub()
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $destinationDataset = $datasetManager->get($extractedArguments['dataset-name']);
        $sourceDataset = $datasetManager->get($input->getArgument('origin-dataset-name'));
        $attributeMappingsAsStr = $datasetManager->get($input->getArgument('attribute-mappings'));

        //!!!CONTINUE
        $attributeMappings = [];
        if ($attributeMappingsAsStr) {
            $attributeMappingsAsRawArray = explode(',', $attributeMappingsAsStr);
            foreach($attributeMappingsAsRawArray as $am) {
                //preg_match('^/.*');
                //$attributeMappings
            }
        }
        
        $componentRecordManager = $destinationDataset->getComponentRecordManager();
        $componentRecordManager->copy($input->getArgument('component-name'), $sourceDataset, $input->getOption('filter'), $input->getOption('existing-only'), $input->getOption('ignore-structure-difference'), $output);
    }
}