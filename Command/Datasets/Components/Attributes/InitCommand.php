<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class InitCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:components:attributes:init')
            ->setDescription('Initialises one or several similar attribute in the component')
            ->makeDatasetAware()
            ->markAsBroken()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-names', InputArgument::REQUIRED, 'Comma-separated names of the attributes to create')
            ->addArgument('attribute-definition', InputArgument::REQUIRED, 'Definition of all created attribute columns')
            ->addArgument('attribute-comment', InputArgument::OPTIONAL, 'Attribute comment')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $componentName = $input->getArgument('component-name');
        $attributeNames = explode(',', $input->getArgument('attribute-names')); 
        $attributeDefinition = $input->getArgument('attribute-definition');
        $attributeComment  = $input->getArgument('attribute-comment');

        $output->write(sprintf('Adding attribute(s) <info>%s</info> in <info>%s</info> in dataset <info>%s</info>...',  implode(', ', $attributeNames), $componentName, $dataset->getFullName()));
        
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentAttributeManager->initAttributes($componentName, $attributeNames, $attributeDefinition, $attributeComment);
        
        $output->writeln(' Done.');
        $output->writeln('<comment>Please don\'t forget to update component initialisation template!</comment>');
    }
}