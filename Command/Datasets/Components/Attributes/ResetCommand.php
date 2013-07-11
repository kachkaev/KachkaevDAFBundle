<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class ResetCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:components:attributes:reset')
            ->setDescription('Resets an attribute of the given dataset component')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-names', InputArgument::REQUIRED, 'Comma-separated names of the attributes to reset')
            ->addArgument('attribute-value', InputArgument::OPTIONAL, 'Value (null by default)', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $componentName = $input->getArgument('component-name');
        $attributeNames = explode(',', $input->getArgument('attribute-names')); 
        $attributeValue = $input->getArgument('attribute-value');
        $output->write(sprintf('Resetting attribute(s) <info>%s</info> in <info>%s</info> in dataset <info>%s</info> to <info>%s</info>...',  implode(', ',$attributeNames), $componentName, $dataset->getFullName(), var_export($attributeValue, true)));
        
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentAttributeManager->resetAttributes($componentName, $attributeNames, $attributeValue);
        
        $output->writeln(' Done.');
    }
}