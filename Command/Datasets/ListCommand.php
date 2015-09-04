<?php

namespace Kachkaev\DAFBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:datasets:list')
            ->setDescription('Lists existing datasets in a given domain')
            ->makeDomainAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $output->writeln(implode("\n", $this->getDatasetManager($input->getArgument('domain-name'))->listNames()));
    }
}