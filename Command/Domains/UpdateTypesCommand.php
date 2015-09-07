<?php

namespace Kachkaev\DAFBundle\Command\Domains;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateTypesCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:domains:update-types')
            ->setDescription('Updates types in a given domain')
            ->makeDomainAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);

        $domainManager = $this->getContainer()->get('daf.domain_manager');
        $domainManager->updateTypes($input->getArgument('domain-name'));
    }
}