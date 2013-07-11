<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Manages dataset component attributes
 *
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 */

class ComponentAttributeManager {
    
    /**
     * @var Dataset
     */
    protected $dataset;

    /**
     *  @var DatasetManager */
    protected $datasetManager;
    
    /**
     * @var ValidatorInterface
     */
    protected $nameValidator;
    
    /**
     *  @var SQLTemplateManager */
    protected $sqlTemplateManager;

    /**
     * @param Dataset $dataset
     */
    public function __construct(Dataset $dataset)
    {
        $this->dataset = $dataset;
        $this->datasetManager = $this->dataset->getDatasetManager();
        $this->sqlTemplateManager = $this->datasetManager->getSQLTemplatManager();
        $this->nameValidator = $this->datasetManager->getValidator('component_name');
    }
        
    /**
     * Add one or several columns to the component table
     */
    public function initAttributes($componentName, $attributeNames, $attributeColumnDefinition)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        
        $this->sqlTemplateManager->run('postgres_helper#datasets/components/attributes/init', [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNames,
                'attributeColumnDefinition'=>$attributeColumnDefinition,
                ]);
    }
    
    /**
     * Returns an array of attribute names [name1, name2, ...]
     * @param string $componentName
     */
    public function listAttributeNames($componentName)
    {
        return array_keys($this->listAttributeNamesAndTypes($componentName));
    }
    
    /**
     * Returns an array of attribute name / type pairs [name1 => postgres type1, name2 => postgres type 2]
     * @param string $componentName
     */
    public function listAttributeNamesAndTypes($componentName)
    {
        return $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * 
     * @param array $attributeNames
     * @param string $errorMessage
     */
    public function assertHavingAttributes($componentName, $attributeNames, $errorMessage = null)
    {
        $missingAttributes = array_diff($attributeNames, $this->listAttributeNames($componentName));
        if (count($missingAttributes)) {
            if (!$errorMessage) {
                $errorMessage = sprintf(count($missingAttributes) == 1 ? 'Attribute %s in component %s does not exist' : 'Attributes %s in component %s do not exist', implode(', ', $missingAttributes), $componentName);
            }
            throw new \LogicException($errorMessage);
        }
    }
    
    public function getAttributesByIds($componentName, $attributeNames, $recordIds)
    {
        // Qutes are removed tom make it possible to typecast attributes
        //$attributeNamesAsStr = '"'.implode('","', $attributeNames).'"';
        $attributeNamesAsStr = ''.implode(',', $attributeNames).'';
        
        $recordIdsAsStr = "'".implode("','", $recordIds)."'";
        
        $result = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/attributes/getByIds", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNamesAsStr'=>$attributeNamesAsStr,
                'recordIdsAsStr'=>$recordIdsAsStr,
                ]);
        
        return $result;
    }

    /**
     * Returns attributes in queried records
     * @param string $componentName
     * @param array $attributeNames
     * @param string $where
     * @return array
     */
    public function getAttributesWhere($componentName, $attributeNames, $where)
    {
        // Quotes are removed tom make it possible to typecast attributes
        //$attributeNamesAsStr = '"'.implode('","', $attributeNames).'"';
        $attributeNamesAsStr = ''.implode(',', $attributeNames).'';
        
        $result = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/attributes/getWhere", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNamesAsStr'=>$attributeNamesAsStr,
                'where'=>$where,
                ]);
        
        return $result;
    }

    public function getIdsWhere($componentName, $where)
    {
        $result = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/attributes/getIdsWhere", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'where'=>$where,
        ], null, \PDO::FETCH_COLUMN);
        
        return $result;
    }
    
    /**
     * Sets an attributes to a given value or null for all records
     * 
     * @param string $componentName
     * @param string|array $attributeNames
     * @param any $value
     */
    public function resetAttributes($componentName, $attributeNames, $value = null, $filter = null)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        $this->sqlTemplateManager->run("postgres_helper#datasets/components/attributes/reset", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNamesAsArray,
                'filter'=>$filter
                ], ['attributeValue'=>$value]);
    }

    /**
     * Sets attributes to a specific value for a list of records with given ids
     * @param Dataset $this->dataset
     * @param string $componentName
     * @param string $attributeNames
     * @param array|string $recordIds one or several record ids
     * @param boolean|null $value
     */
    public function setAttributes($componentName, $attributeNames, $recordIds, $value)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        
        $recordIdsAsStr = "'".implode("','", $recordIds)."'";
    
        $this->sqlTemplateManager->run("postgres_helper#datasets/components/attributes/set", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNamesAsArray,
                'recordIdsAsStr'=>$recordIdsAsStr,
                ], ['attributeValue'=>$value]);
    }
    
    /**
     * Saves given data to the table
     * 
     * @param Dataset $this->dataset
     * @param string $componentName
     * @param string $attributeNames
     * @param array $data id => [attr1Value, attr2Value, ...]
     */
    public function setData($componentName, $attributeNames, $data)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        
        $attributeCount = count($attributeNames);
        foreach ($data as $id => $attributeValues) {
            $this->sqlTemplateManager->run("postgres_helper#datasets/components/attributes/set", [
                    'schema'=>$this->dataset->getSchema(),
                    'datasetName'=>$this->dataset->getName(),
                    'componentName'=>$componentName,
                    'attributeNames'=>$attributeNames,
                    'recordIdAsStr'=>'\''.$id.'\'',
                    ], $attributeValues);
        }
    }
    
    /**
     * Updates attributes in the selected dataset component. Invokes corresponding attribute updaters
     *  
     * @param string $componentName
     * @param string|array $attributeNames
     * @param array $recordIds
     * @throws \RuntimeException
     */
    public function updateAttributes($componentName, $attributeNames, $recordIds)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        
        $attributeNamesToUpdate = $attributeNamesAsArray;

        $updateQueue = [];
        
        // Looking for appropriate attribute updaters
        // and adding them into the queue
        while (count($attributeNamesToUpdate)) {
            $prevNumAttributesToUpdate = count($attributeNamesToUpdate);
            
            foreach($this->datasetManager->getComponentAttributeUpdaters() as $componentAttributeUpdater) {
                $whatThisComponentAttributeUpdaterCanUpdate = $componentAttributeUpdater->listAttributesThatCanUpdate($this->dataset, $componentName, $attributeNamesToUpdate);
                if ($whatThisComponentAttributeUpdaterCanUpdate) {
                    array_push($updateQueue,
                            [
                                'updater' => $componentAttributeUpdater,
                                'attributes' => $whatThisComponentAttributeUpdaterCanUpdate,
                            ]
                        );
                    $attributeNamesToUpdate = array_diff($attributeNamesToUpdate, $whatThisComponentAttributeUpdaterCanUpdate);
                    break;
                }
            }
            if (count($attributeNamesToUpdate) == $prevNumAttributesToUpdate) {
                throw new \RuntimeException(sprintf('Could not find an updater for %s', implode(', ', $attributeNamesToUpdate)));
            }
        }
        
        // Actual updating using assigned updaters
        foreach ($updateQueue as $queueElement) {
            $queueElement['updater']->update($this->dataset, $componentName, $queueElement['attributes'], $recordIds);
        }
    }

    /**
     * Renames an attribute
     * @param string $componentName
     * @param string $attributeName
     * @param string $newAttributeName
     */
    public function renameAttribute($componentName, $attributeName, $newAttributeName)
    {
        if ('id' === $attributeNames) {
            throw new \InvalidArgumentException('Attribute id cannot be renamed');
        }
        
        
        $this->sqlTemplateManager->run("postgres_helper#datasets/components/attributes/rename", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeName'=>$attributeName,
                'newAttributeName'=>$newAttributeName,
            ]);
    }
    
    /**
     * Deletes the given list of attributes (drops columns) 
     * 
     * @param string $componentName
     * @param array|string $attributeNames
     */
    public function deleteAttributes($componentName, $attributeNames)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        
        if (array_search('id', $attributeNames) !== false) {
            throw new \InvalidArgumentException('Attribute id cannot be deleted');
        }
    
        $this->sqlTemplateManager->run("postgres_helper#datasets/components/attributes/delete", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNamesAsArray,
            ]);
    }
}