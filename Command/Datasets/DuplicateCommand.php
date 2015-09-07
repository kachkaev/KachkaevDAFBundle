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
            ->addArgument('duplicate-dataset-name', InputArgument::REQUIRED, 'Name of the dataset (without domain)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $datasetManager->duplicate($extractedArguments['dataset-name'], $input->getArgument("duplicate-dataset-name"));
    }
}