<?php

namespace Kachkaev\DAFBundle\Command\Datasets;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class DeleteCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:delete')
            ->setDescription('Deletes given dataset')
            ->makeDatasetAware()
            ->makeForceAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $datasetName = $extractedArguments['dataset-name'];
        
        if ($datasetManager->has($datasetName)) {
            if (!$input->getOption('force')) {
                $output->writeln('Please run the operation with --force to execute');
                $output->writeln(sprintf('<error>All data in dataset %s will be lost!</error>', $datasetName));
                return;
            } else {
                $datasetManager->delete($datasetName);
                $output->writeln(sprintf('Dataset <info>%s</info> was successfully deleted', $datasetName));
            }
        } else {
            $output->writeln(sprintf('No action was performed - dataset <info>%s</info> didn\'t exist', $datasetName));
        }
    }
}