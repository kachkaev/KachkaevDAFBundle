<?php

namespace Kachkaev\DAFBundle\Command\Datasets\Components\Attributes;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Command\AbstractParameterAwareCommand;

abstract class UpdateCommandAlias extends AbstractParameterAwareCommand
{
    
    private $config;
    
    protected $maxAttributesInDescription = 5;
    protected $attributesInShortenDescription = 3;
    
    public function __construct($name = null)
    {
        $this->config = $this->preconfigure() +  
            [
                'command-description' => null,
                'filter' => null,
                'chunk-size' => null
            ];

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
        if (count($this->config['attributes-to-update']) > $this->maxAttributesInDescription) {
            $listOfAttributesToUpdateAsString = sprintf('%s and %s more', implode(', ', array_slice($this->config['attributes-to-update'], 0, $this->maxAttributesInDescription)), count($this->config['attributes-to-update']) - $this->attributesInShortenDescription);
        } else {
            $listOfAttributesToUpdateAsString = implode(', ', $this->config['attributes-to-update']);
        }
        $this
            ->setName($this->config['command-name'])
            ->setDescription($this->config['command-description'] ?: sprintf('Updates %s in component %s', $listOfAttributesToUpdateAsString, $this->config['component-name']))
            ->makeDatasetAware($this->config['domain-name']);
        
        if (!$this->config['filter']) {
            $this->addOption('filter', null, InputOption::VALUE_REQUIRED,
                    'sql WHERE to filter records and get their ids',
                    null);
        }
        
        if (!$this->config['chunk-size']) {
            $this->addOption('chunk-size', null, InputOption::VALUE_REQUIRED,
                    sprintf('number of records in a batch'),
                    null);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processInput($input, $output, $extractedArguments);
        

        $datasetManager = $this->getDatasetManager($extractedArguments['domain-name']);
        $dataset = $datasetManager->get($extractedArguments['dataset-name']);
        
        $filter = $this->config['filter'] ?: ($input->getOption('filter') ?: 'TRUE');
        
        $invokedCommandName = 'daf:datasets:components:attributes:update';
        $invokedCommand = $this->getApplication()->find($invokedCommandName);
        $invokedCommandArguments = [
                'command' => $invokedCommandName,
                'dataset-full-name' => $dataset->getFullName(),
                'component-name' => $this->config['component-name'],
                'attribute-names' => implode(',', $this->config['attributes-to-update']),
                '--filter' => $filter,
        ];
        
        $chunkSize = $this->config['chunk-size'] ?: $input->getOption('chunk-size');
        if ($chunkSize) {
            $invokedCommandArguments['--chunk-size'] = $chunkSize;
        }
        
        $invokedCommandInput = new ArrayInput($invokedCommandArguments);
        return $invokedCommand->run($invokedCommandInput, $output);
    }
}