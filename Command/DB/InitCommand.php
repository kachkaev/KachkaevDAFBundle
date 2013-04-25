<?php

namespace Kachkaev\PostgresHelperBundle\Command\DB;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Doctrine\DBAL\DriverManager;

class InitCommand extends AbstractParameterAwareCommand
{
    protected $postgisTemplateName = "postgis_template";
    
    protected $defaultSchemas = [
        "photosets",
    ];
    
    protected function configure()
    {
        $this
            ->setName('ph:db:init')
            ->setDescription(sprintf('Initialises the database: clones %s and creates default schemas', $this->postgisTemplateName))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $connection = $this->getContainer()->get('doctrine.dbal.main_connection');
        $sqlTemplateManager = $this->getContainer()->get('postgres_helper.sql_template_manager');
        
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : $params['dbname'];
        unset($params['dbname']);
        
        $tmpConnection = DriverManager::getConnection($params);
        $output->write(sprintf('Cloning postgis template <info>%s</info> into a new database <info>%s</info>...', $this->postgisTemplateName, $name));

        if (!isset($params['path'])) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }
        
        try {
            $query = $sqlTemplateManager->render('postgres_helper#init-db', ['database' => $name, 'template' => $this->postgisTemplateName]);
            $tmpConnection->getWrappedConnection()->exec($query);
            $output->writeln(' Done.');
        } catch (\Exception $e) {
            $tmpConnection->close();
            throw $e;
        }
        
        $output->write(sprintf('Initialising default schemas...'));
        $schemaManager = $this->getContainer()->get('postgres_helper.schema_manager');
        foreach ($this->defaultSchemas as $schemaName) {
            $schemaManager->init($schemaName);
        }
        $output->writeln(" Done.");
    }
}