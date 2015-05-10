<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components\Records;

use Symfony\Component\Console\Input\ArrayInput;

use Kachkaev\DAFBundle\Model\Dataset\AbstractComponentRecordPopulator;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

class PopulateCommand extends AbstractParameterAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('daf:datasets:components:records:populate')
            ->setDescription('Populates the component with records using a corresponding service')
            ->makeDatasetAware()
            ->addArgument('component-name', InputArgument::REQUIRED, 'Name of the component')
            ->addOption('thread-count', 't', InputOption::VALUE_REQUIRED, 'Number of threads to run (only applicable to some populators)', 0)
            ->addOption('gui', 'g', InputOption::VALUE_NONE, 'Turn on gui support (only applicable to some populators)')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        
        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        $componentRecordManager = $dataset->getComponentRecordManager();
        $componentName = $input->getArgument('component-name');
        
        // Create the component if it does not exist
        if (!$dataset->getComponentManager()->has($componentName)) {
            $invokedCommandName = 'daf:datasets:components:init';
            $invokedCommand = $this->getApplication()->find($invokedCommandName);
            $invokedCommandArguments = array(
                    'command' => $invokedCommandName,
                    'dataset-full-name' => $dataset->getFullName(),
                    'component-name' => $componentName,
            );
            
            $invokedCommandInput = new ArrayInput($invokedCommandArguments);
            $invokedCommand->run($invokedCommandInput, $output);
        }
        
        $output->write(sprintf('Populating component <info>%s</info> in dataset <info>%s</info>...', $componentName, $dataset->getFullName()));
        
        $populator = $componentRecordManager->populate($componentName, [
                'thread-count' => $input->getOption('thread-count'),
                'gui' => $input->getOption('gui')
            ], $output);

        $output->writeln(' Done.');
    }
}