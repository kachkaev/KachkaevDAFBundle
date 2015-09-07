<?php

namespace Kachkaev\DAFBundle\Command\Domains;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateFunctionsCommand extends AbstractParameterAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('daf:domains:update-functions')
            ->setDescription('Updates functions in a given domain')
            ->makeDomainAware()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractedArguments = $this->processInput($input, $output);

        $domainManager = $this->getContainer()->get('daf.domain_manager');
        $domainManager->updateFunctions($input->getArgument('domain-name'));
    }
}