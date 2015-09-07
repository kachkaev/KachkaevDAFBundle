<?php

namespace Kachkaev\DAFBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class RenameCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:datasets:rename')
            ->setDescription('Renames given dataset')
            ->makeDatasetAware()
            ->addArgument('dataset-new-name', InputArgument::REQUIRED, 'New name of the dataset (without domain)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);

        $output->write(sprintf('Renaming dataset <info>%s.%s</info> to <info>%s</info>...', $extractedArguments['domain-name'], $extractedArguments['dataset-name'], $input->getArgument("dataset-new-name")));

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $datasetManager->rename($extractedArguments['dataset-name'], $input->getArgument("dataset-new-name"));

        $output->writeln(' Done.');
    }
}