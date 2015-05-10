<?php

namespace Kachkaev\DAFBundle\Command\Schemas;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateTypesCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:schemas:update-types')
            ->setDescription('Updates types in a given schema')
            ->makeSchemaAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $schemaManager = $this->getContainer()->get('daf.schema_manager');
        $schemaManager->updateTypes($input->getArgument('schema'));
    }
}