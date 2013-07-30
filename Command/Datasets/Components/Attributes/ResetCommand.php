<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputOption;

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
            ->makeForceAware()
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-names', InputArgument::REQUIRED, 'Comma-separated names of the attributes to reset')
            ->addArgument('attribute-value', InputArgument::OPTIONAL, 'Value (null by default)', null)
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter (WHERE statement) to choose attributes of what records to reset')
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
        $filter = $input->getOption('filter');
        
        if (strtolower($attributeValue) === "null") {
            $attributeValue = null;
        }
        
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentRecordManager = $dataset->getComponentRecordManager();
        $affectedRecordCount = $datasetComponentRecordManager->count($componentName, $filter);
        
        if ($this->forceNotUsed($input, $output, sprintf('Attribute(s) %s of %s records in component %s of dataset %s will be reset to %s.', implode(', ',$attributeNames), ($filter ? '' : 'all ').number_format($affectedRecordCount), $componentName, $dataset->getFullName(), var_export($attributeValue, true)))) {
            return;
        }
        
        $output->write(sprintf('Resetting attribute(s) <info>%s</info> of %s records in <info>%s</info> in dataset <info>%s</info> to <info>%s</info>...',  implode(', ',$attributeNames), ($filter ? '' : 'all ').number_format($affectedRecordCount), $componentName, $dataset->getFullName(), var_export($attributeValue, true)));
        
        $datasetComponentAttributeManager->resetAttributes($componentName, $attributeNames, $attributeValue, $filter);
        
        $output->writeln(' Done.');
    }
}