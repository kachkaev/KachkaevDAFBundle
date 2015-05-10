<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\DB;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Doctrine\DBAL\DriverManager;

class QueryToFileCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:db:query-to-file')
            ->setDescription('Saves the result of the query to a file')
            ->makePathToFileAware()
            ->makeSQLTemplateNameAware()
            ->makeSQLTemplateParametersAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $sqlTemplateManager = $this->getContainer()->get('dataset_abstraction.sql_template_manager');
        
        $output->write('Saving query result to file...');

        $output->writeln(sprintf('<info>%s</info>', $sqlTemplateManager->render($input->getArgument('sql-template-name'), $input->getArgument('sql-template-parameters'))));
        $sqlTemplateManager->runAndSaveToFile($input->getArgument('path-to-file'), $input->getArgument('sql-template-name'), $input->getArgument('sql-template-parameters'));
        
        $output->writeln(' Done.');
    }
}