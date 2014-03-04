<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\Schemas;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('da:schemas:list')
            ->setDescription('Lists existing database schemas')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $output->writeln(implode("\n", $this->getContainer()->get('dataset_abstraction.schema_manager')->listNames()));
    }
}