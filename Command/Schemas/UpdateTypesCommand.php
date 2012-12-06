<?php

namespace Kachkaev\PostgresHelperBundle\Command\Schemas;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateTypesCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:schemas:update-types')
            ->setDescription('Updates types in a given schema')
            ->makeSchemaAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $schemaManager = $this->getContainer()->get('postgres_helper.schema_manager');
        $schemaManager->updateTypes($input->getArgument('schema'));
    }
}