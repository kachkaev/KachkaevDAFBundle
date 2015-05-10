<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class ResetCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:reset')
            ->setDescription('Deletes all data in the dataset component and recreates it')
            ->makeForceAware()
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component to reset')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $this->processInput($input, $output, $extractedArguments);
        
        if ($input->hasArgument('component-name')) {
            $componentName = $input->getArgument('component-name');
        } else {
            $componentName = $this->componentName;
        }

        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentManager = $dataset->getComponentManager();
        
        if ($this->forceNotUsed($input, $output, sprintf('All data in component %s of the dataset %s will be lost if it exists!', $componentName, $dataset->getFullName()))) {
            return 1;
        }
        
        $output->write(sprintf('Resetting component <info>%s</info> in dataset <info>%s</info>...', $componentName, $dataset->getFullName()));
        $componentManager->reset($componentName);
        $output->writeln(' Done.');
    }
}