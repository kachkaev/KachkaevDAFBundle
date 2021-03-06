<?php

namespace Kachkaev\DAFBundle\Command\DB;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;
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
            ->addArgument('default-domains', InputArgument::OPTIONAL, 'Names of domains to initialise by default (comma-separated, no spaces between)')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);

        $connection = $this->getContainer()->get('doctrine.dbal.main_connection');
        $sqlTemplateManager = $this->getContainer()->get('daf.sql_template_manager');

        // XXX validate template-name
        $templateName = $input->getArgument('template-name');
        if ('null' == $templateName || !$templateName) {
            $templateName = null;
        }

        // XXX validate default domains
        $defaultDomains = $input->getArgument('default-domains');
        if ($defaultDomains)
            $defaultDomains = explode(',', $defaultDomains);


        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : $params['dbname'];
        //unset($params['dbname']);
        $params['dbname'] = 'postgres';

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
            $query = $sqlTemplateManager->render('daf#init-db', ['database' => $name, 'template' => $templateName]);
            $tmpConnection->getWrappedConnection()->exec($query);
            $output->writeln(' Done.');
        } catch (\Exception $e) {
            $tmpConnection->close();
            throw $e;
        }

        if ($defaultDomains) {
            $output->write(sprintf('Initialising default domain%s (<info>%s</info>)...', sizeof($defaultDomains) > 1 ? 's':'', implode('</info>, <info>', $defaultDomains)));
            $domainManager = $this->getContainer()->get('daf.domain_manager');
            foreach ($defaultDomains as $domainName) {
                $domainManager->init($domainName);
            }
            $output->writeln(" Done.");
        }
    }
}