<?php

namespace Kachkaev\PostgresHelperBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Command\AbstractParameterAwareCommand;

abstract class UpdateCommandAlias extends AbstractParameterAwareCommand
{
    
    private $commandName;
    private $commandDescription;
    private $datasetSchemaName;
    private $componentName;
    private $attributesToUpdate;
    
    public function __construct($name = null)
    {
        $config = $this->preconfigure();
        $this->commandName = $config['command-name'];
        if (array_key_exists('command-description', $config)) {
            $this->commandDescription = $config['command-description'];
        }
        $this->datasetSchemaName = $config['dataset-schema'];
        $this->componentName = $config['component-name'];
        $this->attributesToUpdate = $config['attributes-to-update'];

        parent::__construct($name);
    }
    
    /**
     * @return array(
     *     'command-name' => 'namespace:command',
     *     'command-description' => 'Updates xxx in yyy', // optional
     *     'dataset-namespace' => 'my_namespace',
     *     'component-name' => 'my_component',
     *     'attributes-to-update' => array('a1', 'a2', 'aN'),
     * )
     */
    abstract protected function preconfigure();
    
    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription ?: sprintf('Updates %s in component %s', implode(', ', $this->attributesToUpdate), $this->componentName))
            ->makeDatasetAware($this->datasetSchemaName)
            ->addOption('filter', null, InputOption::VALUE_REQUIRED,
                    'sql WHERE to filter records and get their ids',
                    null)
            ->addOption('chunk-size', null, InputOption::VALUE_REQUIRED,
                    sprintf('number of records in a batch'),
                    null)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        

        $datasetManager = $this->getDatasetManager($extractedArguments['dataset-schema']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $filter = $input->getOption('filter') ?: 'TRUE';
        
        $invokedCommandName = 'ph:datasets:components:attributes:update';
        $invokedCommand = $this->getApplication()->find($invokedCommandName);
        $invokedCommandArguments = [
                'command' => $invokedCommandName,
                'dataset-full-name' => $dataset->getFullName(),
                'component-name' => $this->componentName,
                'attribute-names' => implode(',', $this->attributesToUpdate),
                '--filter' => $filter,
        ];
        
        $chunkSize = $input->getOption('chunk-size');
        if ($chunkSize) {
            $invokedCommandArguments['--chunk-size'] = $chunkSize;
        }
        
        $invokedCommandInput = new ArrayInput($invokedCommandArguments);
        return $invokedCommand->run($invokedCommandInput, $output);
    }
}