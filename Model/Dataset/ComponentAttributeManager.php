<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
        
        $this->sqlTemplateManager->run('postgres_helper#datasets/component-attributes/init', [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNames,
                'attributeColumnDefinition'=>$attributeColumnDefinition,
                ]);
    }
    
    public function getAttributesByIds($componentName, $attributeNames, $recordIds)
    {
        $attributeNamesAsStr = '"'.implode('","', $attributeNames).'"';
        $recordIdsAsStr = "'".implode("','", $recordIds)."'";
        
        $result = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-attributes/getByIds", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNamesAsStr'=>$attributeNamesAsStr,
                'recordIdsAsStr'=>$recordIdsAsStr,
                ]);
        
        return $result;
    }


    public function getAttributesWhere($componentName, $attributeNames, $where)
    {
        $attributeNamesAsStr = '"'.implode('","', $attributeNames).'"';
        
        $result = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-attributes/getWhere", [
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
        $result = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-attributes/getIdsWhere", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'where'=>$where,
        ], null, \PDO::FETCH_COLUMN);
        
        return $result;
    }
    
    /**
     * Sets an attributes to a given value or null for all records
     * @param Dataset $this->dataset
     * @param string $componentName
     * @param string|array $attributeNames
     * @param any $value
     */
    public function resetAttributes($componentName, $attributeNames, $value = null)
    {
        if (is_string($attributeNames)) {
            $attributeNamesAsArray = [$attributeNames];
        } else {
            $attributeNamesAsArray = $attributeNames;
        }
        $this->sqlTemplateManager->run("postgres_helper#datasets/component-attributes/reset", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNamesAsArray,
                ], ['attributeValue'=>$value]);
    }

    /**
     * Sets an attributes to a specific value for a list of records with given ids
     * @param Dataset $this->dataset
     * @param string $componentName
     * @param string $attributeName
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
    
        $this->sqlTemplateManager->run("postgres_helper#datasets/component-attributes/set", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeNames'=>$attributeNamesAsArray,
                'recordIdsAsStr'=>$recordIdsAsStr,
                ], ['attributeValue'=>$value]);
    }
    
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
}