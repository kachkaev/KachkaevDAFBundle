<?php
namespace Kachkaev\DAFBundle\Command\Datasets;

use Symfony\Component\Console\Input\ArrayInput;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Kachkaev\DAFBundle\Model\Dataset;
use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class InitCommand extends AbstractParameterAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('daf:datasets:init')
            ->setDescription('Initialises an empty dataset')
            ->makeDatasetAware()
            ->makeDatasetTypeAware(true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(sprintf('Initialising dataset <info>%s</info>...',
                $input->getArgument('dataset-full-name')
            ));
        
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->init($extractedArguments['dataset-name']);
        
        $output->writeln(' Done.');
        
        if ($input->hasArgument('dataset-type')) {
            
            $command = $this->getApplication()->find('daf:datasets:properties:set');
            $arguments = [
                    'dataset-full-name' => $input->getArgument('dataset-full-name'),
                    'property-name' => 'type',
                    'property-value' => $input->getArgument('dataset-type')
            ];
            $input = new ArrayInput($arguments);
            return $command->run($input, $output);
        }
    }
}