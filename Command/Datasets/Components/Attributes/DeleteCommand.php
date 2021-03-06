<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class DeleteCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:attributes:delete')
            ->setDescription('Deletes dataset component attribute (drops a column)')
            ->makeForceAware()
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-names', InputArgument::REQUIRED, 'Comma-separated names of the attributes to delete')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);

        $componentName = $input->getArgument('component-name');
        $attributeNames = explode(',', $input->getArgument('attribute-names'));

        $datasetComponentManager = $dataset->getComponentManager();
        $datasetComponentManager->assertHaving($componentName);
        $datasetComponentAttributeManager = $dataset->getComponentAttributeManager();
        $datasetComponentAttributeManager->assertHavingAttributes($componentName, $attributeNames);

        if ($this->forceNotUsed($input, $output, '')) {
            return 1;
        } else {
            $output->write(sprintf('Deleting attribute(s) <info>%s</info> in component <info>%s</info> of dataset <info>%s</info>...',  implode(', ', $attributeNames), $componentName, $dataset->getFullName()));
            $datasetComponentAttributeManager->deleteAttributes($componentName, $attributeNames);
            $output->writeln(' Done.');
            $output->writeln('<comment>Please don\'t forget to update the component initialisation template if you want to these attributes to be added by default in future.</comment>');
        }
    }
}