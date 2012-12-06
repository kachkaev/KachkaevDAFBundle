<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Symfony\Component\Templating\EngineInterface;

abstract class Dataset
{

    protected $name;
    protected $schema;

    protected $properties;
    protected $components;

    /**
     * @var DatasetManager */
    protected $datasetManager;

    /**
     * @var ComponentManager */
    protected $componentManager;
    
    /**
     * @var SQLTemplateManager */
    protected $sqlTemplateManager;
    
    public function __construct(DatasetManager $datasetManager)
    {
        $this->datasetManager = $datasetManager;
        $this->sqlTemplateManager = $datasetManager->getSQLTemplatManager();
        $this->schema = $datasetManager->getSchema();
    }

    /**
     * Checks whether the object is known by the dataset manager and throws an exception if not.
     * To be used in methods that require the dataset to exist in the database
     * 
     * @throws \RuntimeException
     */
    public function assertExists()
    {
        if ($this->datasetManager->has($this->name))
            throw new \RuntimeException(sprintf('Dataset %s.%s does not exist', $this->schema, $this->name));
    }
    
    /**
     * Updates the name of the dataset from the datasetManager
     */
    public function updateName()
    {
        $this->name = $this->datasetManager->getName($this);
    }
    
    /**
     * Return the name of the dataset
     * @return string
     */
    public function getName()
    {
        return $this->name;        
    }
    
    /**
     * Return the schema of the dataset
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;        
    }
    
    /**
     * Return the full name of the dataset as schema.name
     * @return string
     */
    public function getFullName()
    {
        return sprintf('%s.%s', $this->schema, $this->name);        
    }
    
    // ========================================================================
    // Components
    // ========================================================================
    
    /**
     * Checks if the dataset has a particular component (table)
     * @param string $componentName
     * @return boolean
     */
    public function hasComponent($componentName)
    {
        if (null === $this->components) {
            $this->updateProperties();
        }
        
        return array_key_exists($propertyName, $this->components);
    }
        
    // ========================================================================
    // Properties
    // ========================================================================
    
    /**
     * Checks if the dataset has a particular property (record in "meta" table)
     * @param string $componentName
     * @return boolean
     */
    public function hasProperty($propertyName) {
        if (null === $this->properties) {
            $this->updateProperties();
        }
        
        return array_key_exists($propertyName, $this->properties);
    }
    
    /**
     * Gets the value of the given property.
     * If the property does not exist, null is returned
     * @param string $propertyName
     * @return NULL|string
     */
    public function getProperty($propertyName)
    {
        if (null === $this->properties) {
            $this->updateProperties();
        }
        
        if (!array_key_exists($propertyName, $this->properties)) {
            return null;
        } else {
            return $this->properties[$propertyName];
        }   
    }
    
    /**
     * Sets the value of the given property.
     * If new value is null, the property gets removed from the table
     * @param string $propertyName
     * @param string|NULL $propertyValue
     * @throws \InvalidArgumentException If the value is not string or null
     */
    public function setProperty($propertyName, $propertyValue)
    {
        // Validation of name
        if (!is_string($propertyName)) {
            throw new \InvalidArgumentException(sprintf('Dataset property name must be string, %s given', var_export($propertyName, true)));
        }
        
        // TODO validate name string using by regexp
        
        // Validation of value
        if (!is_null($propertyValue) && !is_string($propertyValue)) {
            throw new \InvalidArgumentException(sprintf('Dataset property value must be string or null, %s given', var_export($propertyValue, true)));
        }

        
        // TODO validate value according to property name

        // Do nothing if the value is the same as it was
        if ($propertyValue === $this->getProperty($propertyName)) {
            return false;
        }
        
        // Create, update or delete the property
        if (null !== $this->getProperty($propertyName)) {
            if (null === $propertyValue) {
                $this->sqlTemplateManager->run('kernel#datasets/properties/delete', [
                        'schema'=>$this->schema,
                        'datasetName'=>$this->name,
                    ], [$propertyName]);
                unset ($this->properties[$propertyName]);
            } else {
                $this->sqlTemplateManager->run('kernel#datasets/properties/update', [
                        'schema'=>$this->schema,
                        'datasetName'=>$this->name,
                    ], [$propertyName, $propertyValue, $propertyName]);
                $this->properties[$propertyName] = $propertyValue;
            }
        } else {
            if (null === $propertyValue) {
            } else {
                $this->sqlTemplateManager->run('kernel#datasets/properties/init', [
                        'schema'=>$this->schema,
                        'datasetName'=>$this->name,
                    ], [$propertyName, $propertyValue]);
                $this->properties[$propertyName] = $propertyValue;
            }
        }
        
        // Update functions if type has changed
        if ($propertyName == 'type') {
            $this->getDatasetManager()->updateFunctions();
        }
    }
    
    /**
     * Returns the list of all properties of the dataset (records in "meta" table)
     * @return array
     */
    public function listProperties()
    {
        if (null === $this->properties) {
            $this->updateProperties();
        }
        
        return $this->properties;
    }
    
    /**
     * Actuates the list of properties from the database
     */
    public function updateProperties()
    {
        $properties = $this->sqlTemplateManager->runAndFetchAll('kernel#datasets/properties/list', [
                'schema'=>$this->schema,
                'datasetName'=>$this->name,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        $this->properties = $properties;
    }
    
    // ========================================================================
    // Dependency Injection
    // ========================================================================
    
    public function getDatasetManager()
    {
        return $this->datasetManager;
    }
    
    public function getComponentManager()
    {
        if (!$this->componentManager)
            $this->componentManager = new ComponentManager($this);
        
        return $this->componentManager; 
    }
    
}
