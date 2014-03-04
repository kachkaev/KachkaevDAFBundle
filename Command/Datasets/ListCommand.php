<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\Datasets;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('da:datasets:list')
            ->setDescription('Lists existing datasets in a given schema')
            ->makeSchemaAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $output->writeln(implode("\n", $this->getDatasetManager($input->getArgument('schema'))->listNames()));
    }
}