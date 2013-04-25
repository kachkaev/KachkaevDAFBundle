<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\ComponentAttributes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class ResetCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:component-attributes:reset')
            ->setDescription('Resets an attribute of the given dataset component')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-name', InputArgument::REQUIRED, 'Name of the attribute to reset')
            ->addArgument('attribute-value', InputArgument::OPTIONAL, 'Value (null by default)', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $componentName = $input->getArgument('component-name');
        $attributeName = $input->getArgument('attribute-name'); 
        $attributeValue = $input->getArgument('attribute-value');
        $output->write(sprintf('Resetting attribute <info>%s</info> in <info>%s</info> in dataset <info>%s</info> to <info>%s</info>...',  $attributeName, $componentName, $dataset->getFullName(), $attributeValue));
        
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentAttributeManager->resetAttribute($componentName, $attributeName, $attributeValue);
        
        $output->writeln(' Done.');
    }
}