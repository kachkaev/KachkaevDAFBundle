<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\Datasets\Properties;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

class SetCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:properties:set')
            ->setDescription('Sets single dataset property')
            ->makeDatasetAware()
            ->addArgument('property-name', InputArgument::REQUIRED, 'Name of a property to set')
            ->addArgument('property-value', InputArgument::OPTIONAL, 'Value of a property to set (leave empty to set to null)', NULL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(sprintf('Setting property <info>%s</info> to <info>%s</info> for dataset <info>%s</info>...',
                $input->getArgument('property-name'),
                $input->getArgument('property-value'),
                $input->getArgument('dataset-full-name')
            ));

        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $dataset->setProperty($input->getArgument('property-name'), $input->getArgument('property-value'));
        
        $output->writeln(' Done.');
    }
}