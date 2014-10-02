<?php

namespace Kachkaev\DatasetAbstractionBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DatasetAbstractionBundle\Command\AbstractParameterAwareCommand;

class DummyCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('da:dummy')
            ->setDescription('https://github.com/symfony/symfony/issues/10531')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}