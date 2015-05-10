<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class UpdateCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:attributes:update')
            ->setDescription('Updates given attributes of the given dataset component')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addArgument('attribute-names', InputArgument::REQUIRED, 'Comma-separated names of the attributes to update')
            ->addOption('ids', null, InputOption::VALUE_REQUIRED,
                    'ids of records to update attributes for',
                    null)
            ->addOption('filter', null, InputOption::VALUE_REQUIRED,
                    'sql WHERE to filter records and get their ids',
                    null)
            ->addOption('chunk-size', null, InputOption::VALUE_REQUIRED,
                    sprintf('number of records in a batch'),
                    null)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
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
        
        $output->writeln(sprintf('Updating attributes for %s records...', number_format(count($ids))));
        $progress = $this->getHelper('progress');
        $progress->start($output, count($ids));
        
        // Update records by chunks
        $chunkSize = $input->getOption('chunk-size') ? : $this->getContainer()->getParameter('daf.batch_chunk_size');
        $idChunks = array_chunk($ids, $chunkSize);
        
        foreach ($idChunks as $idChunk) {
            try {
                $attributeManager->updateAttributes($componentName, $attributeNames, $idChunk, $output);
                $progress->advance(count($idChunk));
            } catch (\Exception $e) {
                $message = $e->getMessage();
                
                // Some (whitelisted) errors that do not break the loop
                $errorIsInWhiteList = false; 
                if (strpos($message, 'parse')) {
                    $errorIsInWhiteList = true;
                }
                
                if ($errorIsInWhiteList) {
                    $output->writeln($e->getMessage());    
                } else {
                    throw $e;
                }
            }
        }
        $progress->finish();
        
        $output->writeln(' Done.');
    }
}