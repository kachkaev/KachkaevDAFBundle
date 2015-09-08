<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Properties;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class UpdateCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:datasets:properties:update')
            ->setDescription('Updates dataset properties that may be derived')
            ->makeDatasetAware()
            ->addArgument('property-names', InputArgument::REQUIRED, 'Comma-separated names ofproperties to update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(sprintf('Updating properties <info>%s</info> in dataset <info>%s</info>...',
                $input->getArgument('property-names'),
                $input->getArgument('dataset-full-name')
            ));

        $extractedArguments = $this->processInput($input, $output);

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $dataset->updateProperties(explode(',', $input->getArgument('property-names')));

        $output->writeln(' Done.');
    }
}