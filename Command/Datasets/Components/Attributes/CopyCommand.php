<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class CopyCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:attributes:copy')
            ->setDescription('Copies given attributes from the same component of another dataset')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('origin-dataset-name', InputArgument::REQUIRED, 'Name of the dataset within the same domain to copy attributes from')
            ->addArgument('attribute-names', InputArgument::REQUIRED, 'Comma-separated names of the attributes to copy')
            ->addOption('ids', null, InputOption::VALUE_REQUIRED,
                    'ids of records to copy attributes for',
                    null)
            ->addOption('filter', null, InputOption::VALUE_REQUIRED,
                    'sql WHERE to filter records and get their ids',
                    null)
            ->addOption('chunk-size', null, InputOption::VALUE_REQUIRED,
                    sprintf('number of records in a batch'),
                    null)
            ->addOption('ignore-missing-records-at-source', null, InputOption::VALUE_NONE, 'Set to true to skip missing records at origin (no error will be reported)')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $sourceDataset = $datasetManager->get($input->getArgument('origin-dataset-name'));
        
        $componentName = $input->getArgument('component-name');
        $attributeNames = explode(',', $input->getArgument('attribute-names'));
        $attributeManager = $dataset->getComponentAttributeManager();
        
        // Extract ids
        if ($input->getOption('ids')) {
            // From an argument
            $ids = explode(',',$input->getOption('ids'));
        } else if ($input->getOption('filter')) {
            // From the filter
            $ids = $attributeManager->getIdsWhere($componentName, $input->getOption('filter'));
        } else {
            throw new \InvalidArgumentException("Either option ids or filter must be defined.");
        }
        
        if (!count($ids)) {
            $output->writeln('<error>No records to process</error>');
            return;
        }
        
        $output->writeln(sprintf('Copying attributes for %s records...', number_format(count($ids))));
        $progress = $this->getHelper('progress');
        $progress->start($output, count($ids));
        
        // Copy records by chunks
        $chunkSize = $input->getOption('chunk-size') ? : $this->getContainer()->getParameter('daf.default_chunk_size');
        $idChunks = array_chunk($ids, $chunkSize);
        
        $recordsAffected = 0;
        $recordsTotal = count($ids);
        foreach ($idChunks as $idChunk) {
            try {
                $recordsAffected += $attributeManager->copyAttributes($componentName, $sourceDataset, $attributeNames, $idChunk, $input->getOption('ignore-missing-records-at-source'));
                $progress->advance(count($idChunk));
            } catch (\Exception $e) {
                throw $e;
            }
        }
        $progress->finish();
        
        if ($recordsTotal != $recordsAffected) {
            $output->writeln(sprintf(' Done (%s records skipped as they were not found in the source component).', number_format($recordsTotal - $recordsAffected)));
        } else {
            $output->writeln(' Done.');
        }
    }
}