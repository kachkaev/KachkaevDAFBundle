<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class DeleteCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:delete')
            ->setDescription('Deletes dataset component (drops a table)')
            ->makeForceAware()
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component to delete')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $extractedArguments = $this->processInput($input, $output);
        
        if ($input->hasArgument('component-name')) {
            $componentName = $input->getArgument('component-name');
        } else {
            $componentName = $this->componentName;
        }

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentManager = $dataset->getComponentManager();
        
        if ($this->forceNotUsed($input, $output, sprintf('All data in component %s of the dataset %s will be lost!', $componentName, $dataset->getFullName()))) {
            return 1;
        }
        
        $output->write(sprintf('Deleting component <info>%s</info> in dataset <info>%s</info>...', $componentName, $dataset->getFullName()));
        $componentManager->delete($componentName);
        $output->writeln(' Done.');
    }
}