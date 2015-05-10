<?php

namespace Kachkaev\DAFBundle\Command\Schemas;

use Doctrine\DBAL\Portability\Connection;

use Doctrine\DBAL\Schema\PostgreSqlSchemaManager;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class DeleteCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:schemas:delete')
            ->setDescription('Deletes given database schema in the main database')
            ->addArgument('schema-name', InputArgument::REQUIRED, 'Name of the schema to delete')
            ->makeForceAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $schemaName = $input->getArgument('schema-name');
        $schemaManager = $this->getContainer()->get('daf.schema_manager');
        
        if ($schemaManager->has($schemaName)) {
            if (!$input->getOption('force')) {
                $output->writeln('Please run the operation with --force to execute');
                $output->writeln('<error>All data in schema '.$schemaName.' will be lost!</error>');
                return;
            } else {
                $schemaManager->delete($schemaName);
                $output->writeln("Schema <info>$schemaName</info> was successfully deleted");
            }
        } else {
            $output->writeln("No action was performed - schema <info>$schemaName</info> didn't exist");
        }
    }
}