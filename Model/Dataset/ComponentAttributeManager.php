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
     * Add a column to the component table
     */
    public function initAttribute($componentName, $attributeName, $attributeColumnDefinition)
    {
        $this->sqlTemplateManager->run('postgres_helper#datasets/component-attributes/init', [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeName'=>$attributeName,
                'attributeColumnDefinition'=>$attributeColumnDefinition,
                ]);
    }
    
    public function calculateAttribute($componentName, $attributeName)
    {
        $schema = $this->dataset->getSchema();
        $type = $this->dataset->getProperty('type');
        
        // TODO look for a service that does custom flagging (not sql-based)

        $templates = [
                "$schema#$componentName/attributes/$attributeName.$type",
                "$schema#$componentName/attributes/$attributeName",
            ];
        
        $this->sqlTemplateManager->run($templates, [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
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
     * @param string $attributeName
     * @param boolean|null $value
     */
    public function resetAttribute($componentName, $attributeName, $value = null)
    {
        $this->sqlTemplateManager->run("postgres_helper#datasets/component-attributes/reset", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeName'=>$attributeName,
                'attributeValue'=>$value,
                ]);
    }

    /**
     * Sets an attributes to a given value for a list of records with given ids
     * @param Dataset $this->dataset
     * @param string $componentName
     * @param string $attributeName
     * @param array|string $recordIds one or several record ids
     * @param boolean|null $value
     */
    public function setAttribute($componentName, $attributeName, $recordIds, $value)
    {
        // TODO look for a service that does custom flagging (not sql-based)
        $schema = $this->dataset->getSchema();
        $type = $this->dataset->getProperty('type');
    
        $recordIdsAsStr = "'".implode("','", $recordIds)."'";
    
        $this->sqlTemplateManager->run("postgres_helper#datasets/component-attributes/set", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'attributeName'=>$attributeName,
                'recordIdsAsStr'=>$recordIdsAsStr,
                ], ['attributeValue'=>$value]);
    }
}