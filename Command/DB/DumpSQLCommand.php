<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\DB;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Doctrine\DBAL\DriverManager;

class DumpSQLCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:db:dump-sql')
            ->setDescription('Runs the query from template and saves the result into a file')
            ->makeSQLTemplateNameAware()
            ->makeSQLTemplateParametersAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $sqlTemplateManager = $this->getContainer()->get('dataset_abstraction.sql_template_manager');
        
        $output->writeln(sprintf('<info>%s</info>', $sqlTemplateManager->render($input->getArgument('sql-template-name'), $input->getArgument('sql-template-parameters'))));
    }
}