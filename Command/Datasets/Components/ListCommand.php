<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:list')
            ->makeDatasetAware()
            ->setDescription('Lists existing components in the dataset')
            ->setDescription('Lists existing components in the dataset')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentManager = $dataset->getComponentManager();
        
        $outputFormatter = $this->getContainer()->get('pr.helper.output_formatter');
        
        $list = $componentManager->listNames();
        if (sizeof($list)) {
            $output->writeln(sprintf('List of components in dataset <info>%s</info>:', $dataset->getFullName()));
            $outputFormatter->outputArrayAsAlignedList($output, $componentManager->listNames());
        } else {
            $output->writeln(sprintf('Dataset <info>%s</info> has no components.', $dataset->getFullName()));
        }
    }
}