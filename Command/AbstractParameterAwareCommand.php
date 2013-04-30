<?php

namespace Kachkaev\PostgresHelperBundle\Command;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractParameterAwareCommand extends ContainerAwareCommand
{
    protected $stub = false;
    protected $broken = false;
    protected $brokenFault = null;
    protected $datasetSchema = null;
    
    protected function makeRecursiveAware($description = 'Execute the command recursively')
    {
        $this->addOption('recursive', 'r', InputOption::VALUE_NONE, $description);

        return $this;
    }

    protected function makeAreaAware()
    {
        $this
                ->addOption('area', 'a', InputOption::VALUE_REQUIRED,
                        'Names of area(s) to work with', 'london');

        return $this;
    }

    protected function makeDatasetAware($datasetSchema = null)
    {
        $this->datasetSchema = $datasetSchema;
        if (!$datasetSchema) {
            $this
                ->addArgument('dataset-full-name', InputArgument::REQUIRED,
                        'Full name of the dataset to work with (e.g. schema.name)');
        } else {
            $this
            ->addArgument('dataset-name', InputArgument::REQUIRED,
                    sprintf('Full name of the dataset within schema %s to work with)', $datasetSchema));
        }
        
        return $this;
    }

    // TODO Implement makeDatasetsAware()
     protected function makeDatasetsAware()
     {
        $this->markAsBroken('makeDatasetsAware() is not implemented');
        //$this->addArgument('dataset-full-names', InputArgument::REQUIRED,
        //       'Full names of the datasets to work with (e.g. schema.name,schema.name2,schema2.name3)');
        
         return $this;
     }
    
     protected function makeDatasetTypeAware($optional = false)
     {
         $this->addArgument('dataset-type', $optional ? InputArgument::OPTIONAL : InputArgument::REQUIRED, 'Type of the new dataset');
     }
      
     protected function makeSchemaAware()
     {
         $this->addArgument('schema', InputArgument::REQUIRED,
             'Schema name');
         
         return $this;
     }
      
    protected function makeForceAware($description = 'Set this parameter to execute the operation')
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, $description);
                
        return $this;
    }
    
    protected function makeSQLTemplateNameAware()
    {
        $this
            ->addArgument('sql-template-name', InputArgument::REQUIRED, 'Name of an SQL template to render, e.g. postgres_helper#init-db');
                
        return $this;
    }
    
    protected function makeSQLTemplateParametersAware($description = 'Path to file')
    {
        $this
            ->addArgument('sql-template-parameters', InputArgument::OPTIONAL, $description);
                
        return $this;
    }
    
    protected function makePathToFileAware()
    {
        $this
            ->addArgument('path-to-file', InputArgument::REQUIRED, 'SQL template parameters in JSON format');
                
        return $this;
    }
    
    protected function markAsStub()
    {
        $this->stub = true;
        
        return $this;
    }

    protected function markAsBroken($fault = null)
    {
        $this->broken = true;
        $this->brokenFault = $fault;
        
        return $this;
    }
    
    public function isStub()
    {
        return $this->stub;
    }
    
    public function isBroken()
    {
        return $this->broken;
    }
    
    protected function processInput(InputInterface $input, OutputInterface $output, &$extractedArguments = [])
    {
        // Parse argument 'area'
        if ($input->hasOption('area') && !is_null($input->getOption('area')))
            $input->setOption('area', explode(',', $input->getOption('area')));

        // Parse argument 'dataset-full-name'
        $fullnameRegexp = '/^([a-z]+(_[a-z]+)*)\.([a-z_0-9]+)$/';
        if ($input->hasArgument('dataset-full-name') && !is_null($input->getArgument('dataset-full-name'))) {
            $fullName = $input->getArgument('dataset-full-name');
            
            $matches = null;
            if (!preg_match($fullnameRegexp, $fullName, $matches)) {
                throw new \InvalidArgumentException(sprintf('Wrong value for dataset-full-name: %s. It must have the format schema.name.', var_export($fullName, true)));
            }
            
            $schema = $matches[1];
            $name = $matches[3];
            
            $extractedArguments['dataset-full-name'] = $schema.'.'.$name;
            $extractedArguments['dataset-schema'] = $schema;
            $extractedArguments['dataset-name'] = $name;
        }
        
        // Parse argument 'dataset-name'
        if ($input->hasArgument('dataset-name') && !is_null($input->getArgument('dataset-name'))) {
            $name = $input->getArgument('dataset-name');
            $extractedArguments['dataset-full-name'] = $this->datasetSchema.'.'.$name;
            $extractedArguments['dataset-schema'] = $this->datasetSchema;
            $extractedArguments['dataset-name'] = $name;
        }
        
        // TODO Verify arguments 'dataset-name', 'dataset-schema'
        
        if ($this->isStub()) {
            $output->writeln('<error>This command is not implemented yet.</error>');
        }
        
        if ($input->hasArgument('sql-template-parameters')  && !is_null($input->getArgument('sql-template-parameters'))) {
            $input->setArgument('sql-template-parameters', json_decode($input->getArgument('sql-template-parameters'), true));
            if (is_null($input->getArgument('sql-template-parameters'))) {
                throw new \InvalidArgumentException('Malformed json format in sql-template-parameters');
            }
        }
        
        
    }
    
    public function getDescription($addStubPrefix = true)
    {
        $result = parent::getDescription();
        
        if ($addStubPrefix) {
            if ($this->isBroken()) {
                $result = '<error>[broken]</error> '.$result;
            } elseif ($this->isStub()) {
                $result = '<comment>[stub]</comment> '.$result;
            }
        }
        
        return $result;
    }
    
    public function getDatasetManager($schema) {
        
        $this->getContainer()->get('postgres_helper.validator.schema_name')->assertValid($schema);
        
        $serviceName = sprintf('ph.dataset_manager.%s', $schema);
        if ($this->getContainer()->has($serviceName)) {
            return $this->getContainer()->get($serviceName);
        } else {
            throw new \InvalidArgumentException(sprintf('Schema %s does not exist or does not have a dataset manager', $schema));
        }
    }
    
    public function forceNotUsed($input, $output, $warningMessage)
    {
        if (!$input->getOption('force')) {
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln(sprintf('<error>%s</error>', $warningMessage));
            return true;
        }
        return false;
    }
}
