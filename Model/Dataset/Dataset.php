<?php
namespace Kachkaev\DAFBundle\Model\Dataset;

use Kachkaev\DAFBundle\Model\TemplateManaging\SQLTemplateManager;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Intl\Exception\NotImplementedException;

abstract class Dataset
{

    protected $name;
    protected $domainName;

    protected $properties;
    protected $components;

    /**
     * @var DatasetManager */
    protected $datasetManager;

    /**
     * @var ComponentManager */
    protected $componentManager;

    /**
     * @var ComponentAttributeManager */
    protected $componentAttributeManager;

    /**
     * @var ComponentRecordManager */
    protected $componentRecordManager;


    /**
     * @var SQLTemplateManager */
    protected $sqlTemplateManager;

    public function __construct(DatasetManager $datasetManager)
    {
        $this->datasetManager = $datasetManager;
        $this->sqlTemplateManager = $datasetManager->getSQLTemplatManager();
        $this->domainName = $datasetManager->getDomainName();
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
            throw new \RuntimeException(sprintf('Dataset %s.%s does not exist', $this->domainName, $this->name));
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
     * Return the domain of the dataset
     * @return string
     */
    public function getDomainName()
    {
        return $this->domainName;
    }

    /**
     * Return the full name of the dataset as domain_name.dataset_name
     * @return string
     */
    public function getFullName()
    {
        return sprintf('%s.%s', $this->domainName, $this->name);
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
            $this->fetchProperties();
        }

        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * Does nothing if given property exists, throws an exception otherwise
     * @throws \LogicException if given property does not exist
     */
    public function assertHavingProperty($propertyName, $errorMessage = null)
    {
        if (!$errorMessage) {
            $errorMessage = sprintf('The dataset must have %s property defined', $propertyName);
        }

        if (!$this->hasProperty($propertyName)) {
            throw new \LogicException($errorMessage);
        }
    }

    /**
     * Does nothing if given property does not exist, throws an exception otherwise
     * @throws \LogicException if given property exists
     */
    public function assertNotHavingProperty($propertyName, $errorMessage = null)
    {
        if (!$errorMessage) {
            $errorMessage = sprintf('The dataset must not have %s property defined', $propertyName);
        }

        if ($this->hasProperty($propertyName)) {
            throw new \LogicException($errorMessage);
        }
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
            $this->fetchProperties();
        }

        if (!array_key_exists($propertyName, $this->properties)) {
            return null;
        } else {
            return $this->properties[$propertyName];
        }
    }

    public function getComponentProperty($componentName, $propertyName)
    {
        return $this->getProperty(sprintf('%s.%s', $componentName, $propertyName));
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
                $this->sqlTemplateManager->run('daf#datasets/properties/delete', [
                        'domainName'=>$this->domainName,
                        'datasetName'=>$this->name,
                    ], [$propertyName]);
                unset ($this->properties[$propertyName]);
            } else {
                $this->sqlTemplateManager->run('daf#datasets/properties/update', [
                        'domainName'=>$this->domainName,
                        'datasetName'=>$this->name,
                    ], [$propertyName, $propertyValue, $propertyName]);
                $this->properties[$propertyName] = $propertyValue;
            }
        } else {
            if (null === $propertyValue) {
            } else {
                $this->sqlTemplateManager->run('daf#datasets/properties/init', [
                        'domainName'=>$this->domainName,
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

    public function setComponentProperty($componentName, $propertyName, $propertyValue)
    {
        return $this->setProperty(sprintf('.%s.%s', $componentName, $propertyName, $propertyValue));
    }

    /**
     * Sets the value of the given property.
     * If new value is null, the property gets removed from the table
     * @param string $propertyName
     * @param string|NULL $propertyValue
     * @throws \InvalidArgumentException If the value is not string or null
     */
    public function updateProperties($propertyNames)
    {
        // TODO implement
        var_dump($propertyNames);
        throw new NotImplementedException("updateProperties() method has not been implemented yet.");
    }


    /**
     * Actuates the list of properties from the database
     */
    public function fetchProperties()
    {
        $properties = $this->sqlTemplateManager->runAndFetchAll('daf#datasets/properties/list', [
            'domainName'=>$this->domainName,
            'datasetName'=>$this->name,
        ], null, \PDO::FETCH_KEY_PAIR);

        $this->properties = $properties;
    }

    /**
     * Returns the list of all properties of the dataset (records in "meta" table)
     * @return array
     */
    public function listProperties()
    {
        if (null === $this->properties) {
            $this->fetchProperties();
        }

        return $this->properties;
    }

    /**
     * Returns the list of all properties of the dataset component (filtered records in "meta" table)
     * @return array
     */
    public function listComponentProperties($componentName)
    {
        $requiredPropertyPrefix = $componentName.'.';
        $componentProperties = [];
        foreach($this->listProperties() as $propertyName => $propertyValue) {
            if (strpos($propertyName, $requiredPropertyPrefix) === 0) {
                $componentProperties[substr($propertyName, strlen($requiredPropertyPrefix))] = $propertyValue;
            }
        }
        return $componentProperties;
    }


    // ========================================================================
    // Dependency Injection
    // ========================================================================

    /**
     * @return DatasetManager
     */
    public function getDatasetManager()
    {
        return $this->datasetManager;
    }

    /**
     *
     * @return ComponentManager
     */
    public function getComponentManager()
    {
        if (!$this->componentManager)
            $this->componentManager = new ComponentManager($this);

        return $this->componentManager;
    }

    /**
     *
     * @return ComponentAttributeManager
     */
    public function getComponentAttributeManager()
    {
        if (!$this->componentAttributeManager)
            $this->componentAttributeManager = new ComponentAttributeManager($this);

        return $this->componentAttributeManager;
    }

    /**
     * @return ComponentRecordManager
     */
    public function getComponentRecordManager()
    {
        if (!$this->componentRecordManager)
            $this->componentRecordManager = new ComponentRecordManager($this);

        return $this->componentRecordManager;
    }

}
