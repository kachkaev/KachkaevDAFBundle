<?php

namespace Kachkaev\DAFBundle\Command\Domains;

use Doctrine\DBAL\Portability\Connection;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class DeleteCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:domains:delete')
            ->setDescription('Deletes given database domain in the main database')
            ->addArgument('domain-name', InputArgument::REQUIRED, 'Name of the domain to delete')
            ->makeForceAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);

        $domainName = $input->getArgument('domain-name');
        $domainManager = $this->getContainer()->get('daf.domain_manager');

        if ($domainManager->has($domainName)) {
            if (!$input->getOption('force')) {
                $output->writeln('Please run the operation with --force to execute');
                $output->writeln('<error>All data in domain '.$domainName.' will be lost!</error>');
                return;
            } else {
                $domainManager->delete($domainName);
                $output->writeln("Domain <info>$domainName</info> was successfully deleted");
            }
        } else {
            $output->writeln("No action was performed - domain <info>$domainName</info> didn't exist");
        }
    }
}