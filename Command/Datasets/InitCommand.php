<?php
namespace Kachkaev\PostgresHelperBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Kachkaev\PostgresHelperBundle\Model\Dataset;
use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

class InitCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ph:datasets:init')
            ->setDescription('Initialises an empty dataset')
            ->makeDatasetAware()
            ->makeDatasetTypeAware(true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->init($extractedArguments['dataset-name']);
        
        if ($input->hasArgument('dataset-type')) {
            $dataset->setProperty('type', $input->getArgument('dataset-type'));
        }
    }
}