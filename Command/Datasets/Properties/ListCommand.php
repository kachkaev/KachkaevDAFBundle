<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Properties;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:datasets:properties:list')
            ->setDescription('Lists existing dataset properties')
            ->makeDatasetAware()
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $properties = $dataset->listProperties();
        
        $outputFormatter = $this->getContainer()->get('pr.helper.output_formatter');
        $outputFormatter->outputArrayAsAlignedList($output, $properties);
    }
}