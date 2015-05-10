<?php
namespace Kachkaev\DAFBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class DuplicateCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:duplicate')
            ->setDescription('Renames given dataset')
            ->makeDatasetAware()
            ->addArgument('duplicate-dataset-name', InputArgument::REQUIRED, 'Name of the dataset (without schema)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $datasetManager->duplicate($extractedArguments['dataset-name'], $input->getArgument("duplicate-dataset-name"));
    }
}