<?php

namespace Kachkaev\DatasetAbstractionBundle\Command\DB;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Doctrine\DBAL\DriverManager;

class InitCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:db:init')
            ->setDescription('Initialises the database (can use given template)')
            ->addArgument('template-name', InputArgument::OPTIONAL, 'Name of the postgres template to use')
            ->addArgument('default-schemas', InputArgument::OPTIONAL, 'Names of schemas to initialise by default (comma-separated, no spaces between)')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $connection = $this->getContainer()->get('doctrine.dbal.main_connection');
        $sqlTemplateManager = $this->getContainer()->get('dataset_abstraction.sql_template_manager');
        
        // XXX validate template-name
        $templateName = $input->getArgument('template-name'); 
        if ('null' == $templateName || !$templateName) {
            $templateName = null;
        }
        
        // XXX validate default schemas
        $defaultSchemas = $input->getArgument('default-schemas');
        if ($defaultSchemas)
            $defaultSchemas = explode(',', $defaultSchemas);
        
        
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : $params['dbname'];
        unset($params['dbname']);
        
        $tmpConnection = DriverManager::getConnection($params);
        if ($templateName) {
            $output->write(sprintf('Cloning template <info>%s</info> into a new database <info>%s</info>...', $templateName, $name));
        } else {
            $output->write(sprintf('Creating a new database <info>%s</info>...', $name));
        }

        if (!isset($params['path'])) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }
        
        try {
            $query = $sqlTemplateManager->render('dataset_abstraction#init-db', ['database' => $name, 'template' => $templateName]);
            $tmpConnection->getWrappedConnection()->exec($query);
            $output->writeln(' Done.');
        } catch (\Exception $e) {
            $tmpConnection->close();
            throw $e;
        }
        
        if ($defaultSchemas) {
            $output->write(sprintf('Initialising default schema%s (<info>%s</info>)...', sizeof($defaultSchemas) > 1 ? 's':'', implode('</info>, <info>', $defaultSchemas)));
            $schemaManager = $this->getContainer()->get('dataset_abstraction.schema_manager');
            foreach ($defaultSchemas as $schemaName) {
                $schemaManager->init($schemaName);
            }
            $output->writeln(" Done.");
        }
    }
}