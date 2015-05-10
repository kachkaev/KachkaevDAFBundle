<?php

namespace Kachkaev\DAFBundle\Command\Schemas;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:schemas:list')
            ->setDescription('Lists existing database schemas')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $output->writeln(implode("\n", $this->getContainer()->get('daf.schema_manager')->listNames()));
    }
}