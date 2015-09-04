<?php

namespace Kachkaev\DAFBundle\Command\Domains;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:domains:list')
            ->setDescription('Lists existing data domains')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);

        $output->writeln(implode("\n", $this->getContainer()->get('daf.domain_manager')->listNames()));
    }
}