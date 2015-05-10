<?php

namespace Kachkaev\DAFBundle\Command\Schemas;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateFunctionsCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:schemas:update-functions')
            ->setDescription('Updates functions in a given schema')
            ->makeSchemaAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);

        $schemaManager = $this->getContainer()->get('daf.schema_manager');
        $schemaManager->updateFunctions($input->getArgument('schema'));
    }
}