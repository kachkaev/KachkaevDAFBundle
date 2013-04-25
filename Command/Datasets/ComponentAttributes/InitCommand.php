<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\ComponentAttributes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class InitCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:component-attributes:init')
            ->setDescription('Initialises an attribute in the component (column in a table)')
            ->makeDatasetAware()
            ->markAsBroken()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-name', InputArgument::REQUIRED, 'Name of the attribute to initialise')
            ->addArgument('attribute-definition', InputArgument::REQUIRED, 'Definition of the attribute column')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $componentName = $input->getArgument('component-name');
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $attributeName = $input->getArgument('attribute-name'); 
        $attributeDefinition = $input->getArgument('attribute-definition');
        $output->write(sprintf('Adding attribute <info>%s</info> in <info>%s</info> in dataset <info>%s</info>...',  $attributeName, $componentName, $dataset->getFullName()));
        
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentAttributeManager->initAttribute($componentName, $attributeName, $attributeDefinition);
        
        $output->writeln(' Done.');
        $output->writeln('<comment>Please don\'t forget to update component initialisation template!</comment>');
    }
}