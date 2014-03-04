<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

class RenameCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('da:datasets:components:attributes:rename')
            ->setDescription('Renames the dataset component attribute (drops a column)')
            ->makeForceAware()
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-name', InputArgument::REQUIRED, 'Name of the attribute to rename')
            ->addArgument('new-attribute-name', InputArgument::REQUIRED, 'New name of the attribute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $componentName = $input->getArgument('component-name');
        $attributeName = $input->getArgument('attribute-name');
        $newAttributeName = $input->getArgument('new-attribute-name');
        
        $datasetComponentManager = $dataset->getComponentManager();
        $datasetComponentManager->assertHaving($componentName);
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentAttributeManager->assertHavingAttributes($componentName, [$attributeName]);
        
        if ($this->forceNotUsed($input, $output, '')) {
            return 1;
        } else {
            $output->write(sprintf('Renaming attribute <info>%s</info> in component <info>%s</info> of dataset <info>%s</info> into <info>%s</info>...',  $attributeName, $componentName, $dataset->getFullName(), $newAttributeName));
            $datasetComponentAttributeManager->renameAttribute($componentName, $attributeName, $newAttributeName);
            $output->writeln(' Done.');
            $output->writeln('<comment>Please don\'t forget to update component initialisation template!</comment>');
        }
    }
}