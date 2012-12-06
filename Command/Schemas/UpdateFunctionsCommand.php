<?php

namespace Kachkaev\PostgresHelperBundle\Command\Schemas;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateFunctionsCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('ph:schemas:update-functions')
            ->setDescription('Updates functions in a given schema')
            ->makeSchemaAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $schemaManager = $this->getContainer()->get('postgres_helper.schema_manager');
        $schemaManager->updateFunctions($input->getArgument('schema'));
    }
}