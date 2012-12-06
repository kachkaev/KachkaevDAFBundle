<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class PopulateCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:components:populate')
            ->setDescription('Populates dataset component runs a query or a script')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component to populate')
            ->makeForceAware('Forces initialisation of the component before populating to avoid an error if it already ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $componentName = $input->getArgument('component-name');
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentManager = $dataset->getComponentManager();

        if (!$componentManager->has($componentName) && $input->getOption('force')) {
            $command = $this->getApplication()->find('ph:datasets:components:init');
            
            $arguments = array(
                    'command' => 'ph:datasets:components:init',
                    'dataset-full-name' => $extractedArguments['dataset-full-name'],
                    'component-name'    => $componentName,
            );
            
            $input = new ArrayInput($arguments);
            $returnCode = $command->run($input, $output);
        }
        $output->write(sprintf('Populating component <info>%s</info> in dataset <info>%s</info>...', $componentName, $dataset->getFullName()));
        
        $componentManager->populate($componentName);
        
        $output->writeln(' Done.');
    }
}