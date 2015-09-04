<?php

namespace Kachkaev\DAFBundle\Command\Domains;

use Symfony\Component\Console\Input\InputArgument;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class InitCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:domains:init')
            ->setDescription('Initialises given database domain in the main database')
            ->addArgument('domain-name', InputArgument::REQUIRED, 'Name of the domain to initialise')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output);
        
        $domainName = $input->getArgument('domain-name');
        
        $output->write(sprintf('Initialising domain <info>%s</info>...', $domainName));
        $this->getContainer()->get('daf.domain_manager')->init($domainName);
        $output->writeln(' Done.');
    }
}