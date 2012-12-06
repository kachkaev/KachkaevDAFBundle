<?php

namespace Kachkaev\PostgresHelperBundle\Command\Schemas;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class InitCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:schemas:init')
            ->setDescription('Initialises given database schema in the main database')
            ->addArgument('schema-name', InputArgument::REQUIRED, 'Name of the schema to initialise')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $schemaName = $input->getArgument('schema-name');
        
        $output->write(sprintf('Initialising schema <info>%s</info>...', $schemaName));
        $this->getContainer()->get('postgres_helper.schema_manager')->init($schemaName);
        $output->writeln(' Done.');
    }
}