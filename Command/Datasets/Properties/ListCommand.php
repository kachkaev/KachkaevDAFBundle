<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Properties;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:properties:list')
            ->setDescription('Lists existing dataset properties')
            ->makeDatasetAware()
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $properties = $dataset->listProperties();
        
        $outputFormatter = $this->getContainer()->get('pr.helper.output_formatter');
        $outputFormatter->outputArrayAsAlignedList($output, $properties);
    }
}