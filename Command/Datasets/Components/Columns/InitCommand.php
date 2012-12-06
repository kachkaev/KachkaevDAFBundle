<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components\Columns;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class InitCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:components:columns:init')
            ->setDescription('Initialises a column in the component (table)')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('column-name', InputArgument::REQUIRED, 'Name of the column to initialise')
            ->addArgument('column-definition', InputArgument::REQUIRED, 'Name of the column to initialise')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $componentName = $input->getArgument('component-name');
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $columnName = $input->getArgument('column-name'); 
        $columnDefinition = $input->getArgument('column-definition');
        $output->write(sprintf('Adding column <info>%s</info> in <info>%s</info> in dataset <info>%s</info>...',  $columnName, $componentName, $dataset->getFullName()));
        
        $componentManager = $dataset->getComponentManager();
        $componentManager->initColumn($componentName, $columnName, $columnDefinition);
        
        $output->writeln(' Done.');
        $output->writeln('<comment>Please don\'t forget to update component initialisation template!</comment>');
    }
}