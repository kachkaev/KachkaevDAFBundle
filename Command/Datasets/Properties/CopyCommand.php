<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\Datasets\Properties;

use Kachkaev\DatasetAbstractionBundle\Helper\OutputFormatter;

use Symfony\Component\Console\Helper\TableHelper;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

class CopyCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('da:datasets:properties:copy')
            ->setDescription('Copies properties from the origin dataset to the given dataset')
            ->makeDatasetAware()
            ->makeForceAware()
            ->addArgument('origin-dataset-name', InputArgument::REQUIRED, 'Name of the dataset within the same schema to copy data from')
            ->addArgument('property-names', InputArgument::OPTIONAL, 'Names of properties separated by commas')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $destinationDataset = $datasetManager->get($extractedArguments['dataset-name']);
        $sourceDataset = $datasetManager->get($input->getArgument('origin-dataset-name'));
        
        $propertyNames = explode(',', $input->getArgument('property-names'));
        $propertiesInSource =  $sourceDataset->listProperties();
        $propertiesInDestination =  $destinationDataset->listProperties();
        
        if (count($propertyNames) == 1 && $propertyNames[0] == '') {
            // By default all properties are copied
            $propertyNames = array_keys($propertiesInSource);
        } else {
            // Check whether required properties exist in source dataset
            $propertiesThatDontExist = array_diff($propertyNames, array_keys($propertiesInSource));
            if (count($propertiesThatDontExist)) {
                throw new \InvalidArgumentException(sprintf(
                        count($propertiesThatDontExist) == 1 ? "Property %s does not exist in the source dataset." : "Properies %s do not exist in the source dataset.",
                        implode(',',$propertiesThatDontExist)
                    ));
            }
        } 
        
        $exactValuesCount = 0;
        
        // Check whether any of the values should be replaced
        $valueReplacements = [];
        foreach ($propertiesInDestination as $key => $value) {
            if (array_search($key, $propertyNames) === false) {
                continue;
            }
            if (array_key_exists($key, $propertiesInSource)) {
                $valueInSource = $propertiesInSource[$key];
                if ($value !== $valueInSource) {
                    $valueReplacements []= [$key, $valueInSource, $value];
                } else {
                    ++$exactValuesCount;
                }
            }
        }
        
        if (count($valueReplacements) && $this->forceNotUsed($input, $output, count($valueReplacements) == 1 ? "The following property will be overwritten:" : "The following properties will be overwritten:")) {
            
            $table = $this->getHelper('table');
            $table
            ->setHeaders(array('key', 'old value', 'new value'))
            ->setRows($valueReplacements)
            ->setLayout(TableHelper::LAYOUT_BORDERLESS)
            ;
            $table->render($output);
            
            exit (1);
        } else {
            $output->write(sprintf(count($propertyNames) != 1 ? sprintf('Copying %d properties...', count($propertyNames)) : 'Copying 1 property...'));
        }
        
        foreach ($propertyNames as $propertyName) {
            $destinationDataset->setProperty($propertyName, $sourceDataset->getProperty($propertyName));
        }
        $output->write(' Done.');
        if ($exactValuesCount > 0) {
            $output->writeln(sprintf(' %s propet%s remained the same.', $exactValuesCount, $exactValuesCount ? 'y' : 'ies'));
        } else {
            $output->writeln('');
        }
    }
}