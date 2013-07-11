<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Manages dataset component records (rows)
 *
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 */

class ComponentRecordManager {
    
    /**
     * @var Dataset
     */
    protected $dataset;

    /**
     *  @var DatasetManager */
    protected $datasetManager;
    
    /**
     *  @var SQLTemplateManager */
    protected $sqlTemplateManager;
    
    /**
     *  @var ContainerInterface */
    protected $container;

    /**
     * @param Dataset $dataset
     */
    public function __construct(Dataset $dataset)
    {
        $this->dataset = $dataset;
        $this->datasetManager = $this->dataset->getDatasetManager();
        $this->sqlTemplateManager = $this->datasetManager->getSQLTemplatManager();
        $this->container = $this->datasetManager->getContainer();
    }
        
    public function populate($componentName, array $options, OutputInterface $output = null)
    {
        $this->dataset->getComponentManager()->assertHaving($componentName, sprintf('The dataset is missing the ‘%s’ component', $componentName));
        
        /** @var AbstractComponentRecordpopulator
         */
        $populator = null;
        
        $populatorServiceNames = [];
        if ($this->dataset->getProperty('type') !== null) {
            $populatorServiceNames []= sprintf('ph.dataset_component_record_populator.%s.%s.%s', $this->dataset->getSchema(), $componentName, $this->dataset->getProperty('type'));
        };
        $populatorServiceNames []= sprintf('ph.dataset_component_record_populator.%s.%s', $this->dataset->getSchema(), $componentName);

        foreach ($populatorServiceNames as $populatorServiceName) {
            if ($this->container->has($populatorServiceName)) {
                $populator = $this->container->get($populatorServiceName);
                break;
            }
        }
        
        if ($populator == null) {
            throw new \LogicException(sprintf('Data populator not found. Were looking for services %s.', implode(', ', $populatorServiceNames)));
        }
        
        $populator->populate($this->dataset, $options, $output);

    }
    
    /**
     * 
     * @param string $componentName
     * @param string $filter
     */
    public function count($componentName, $filter)
    {
        return $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/records/count", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'filter'=>$filter,
                ])[0]['count'];
    }
    
    /**
     * Returns count of records that both exist in the same component of $dataset2 and the current dataset
     * 
     * @param string $componentName
     * @param Dataset $dataset2
     * @param string $filterForDataset2
     */
    public function countIntersectingIds($componentName, Dataset $dataset2, $filterForDataset2)
    {
        return $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/records/count-intersecting-ids", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'dataset2Name'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'filterForDataset2'=>$filterForDataset2,
            ])[0]['count'];
    }
    
    /**
     * Returns ids of records that both exist in the same component of $dataset2 (or its filtered version) and the current dataset
     * 
     * @param string $componentName
     * @param Dataset $dataset2
     * @param string $filterForDataset2
     * @return array 
     */
    public function listIntersectingIds($componentName, Dataset $dataset2, $filterForDataset2)
    {
        return $this->sqlTemplateManager->runAndFetchAllAsList("postgres_helper#datasets/components/records/list-intersecting-ids", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'dataset2Name'=>$dataset2->getName(),
                'componentName'=>$componentName,
                'filterForDataset2'=>$filterForDataset2,
            ]);
    }
    
    /**
     * Cleans (deletes) records in the component
     * 
     * @param string $componentName
     * @param string|array $filterOrIds string (a=1 AND b=2) or an array of ids
     */
    public function clean($componentName, $filterOrIds = null)
    {
        if (is_string($filterOrIds) || is_null($filterOrIds)) {
            $this->sqlTemplateManager->run("postgres_helper#datasets/components/records/clean-by-filter", [
                    'schema'=>$this->dataset->getSchema(),
                    'datasetName'=>$this->dataset->getName(),
                    'componentName'=>$componentName,
                    'filter'=>$filterOrIds,
                    ]);
        } elseif (is_array($filterOrIds)) {
            
            $idChunks = array_chunk($filterOrIds, 1000);
            
            foreach ($idChunks as $idChunk) {
                $this->sqlTemplateManager->run("postgres_helper#datasets/components/records/clean-by-ids", [
                        'schema'=>$this->dataset->getSchema(),
                        'datasetName'=>$this->dataset->getName(),
                        'componentName'=>$componentName,
                        'idsAsStr'=>"'" . implode("','", $idChunk) . "'",
                    ]);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Wrong value for $filter: %s', var_export($filter, true)));
        }
    }
    
    /**
     * Copies records from the given dataset component to the current dataset
     *  
     * @param string $componentName
     * @param Dataset $sourceDataset
     * @param string $filter
     * @param boolean $existingOnly
     * @param boolean $ignoreAttributeMismatch
     * @param array $attributeMappings associative array of attribute (column) names that need to be renamed / casted, e.g. myfield=>myfield_with_new_name or myfield::int=>myfield_of_new_type 
     */
    public function copy($componentName, Dataset $sourceDataset, $filter, $existingOnly, $ignoreAttributeMismatch, array $attributeMappings)
    {
        // Check if source is compatible with destination
        if ($sourceDataset->getSchema() != $this->dataset->getSchema()) {
            throw new \InvalidArgumentException(sprintf('Schema names mismatch: %s vs. %s', $this->dataset->getSchema(), $sourceDataset->getSchema()));
        }
        
        // List attributes
        // -- source
        $sourceAttributes = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$sourceDataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        // -- destination
        $destinationAttributes = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/components/attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        $attributesSourceNotDestination = array_diff_assoc($sourceAttributes, $destinationAttributes);
        $attributesDestinationNotSource = array_diff_assoc($destinationAttributes, $sourceAttributes);
        $attributesInBoth = array_intersect_assoc($sourceAttributes, $destinationAttributes);
        
        // Check mappings a::type->b
        foreach ($attributeMappings as $attributeAndTypeFrom => $attributeTo) {
            $attributeFrom = $attributeAndTypeFrom;
            if (strpos($attributeFrom, ':') !== false) {
                $attributeFrom = substr($attributeFrom, 0, strpos($attributeFrom, ':'));
            }
            
            if (!array_key_exists($attributeFrom, $sourceAttributes)) {
                throw new \InvalidArgumentException(sprintf('Cannot find attribute %s in the source component among %s', $attributeFrom, implode(', ', array_keys($sourceAttributes))));
            }
            if (!array_key_exists($attributeTo, $destinationAttributes)) {
                throw new \InvalidArgumentException(sprintf('Cannot find attribute %s in the destination component among %s', $attributeTo, implode(', ', array_keys($destinationAttributes))));
            }
            unset ($attributesSourceNotDestination[$attributeFrom]);
            unset ($attributesDestinationNotSource[$attributeTo]);
        }
        
        if ((count($attributesSourceNotDestination) || count($attributesDestinationNotSource)) && !$ignoreAttributeMismatch) {
            throw new \RuntimeException(sprintf("Attributes in source and destination mismatch!\nExist in source only: %s,\nExist in destination only: %s.", var_export($attributesSourceNotDestination, true), var_export($attributesDestinationNotSource, true)));
        }
        
        $attributeNamesInSource = array_merge(array_keys($attributesInBoth), array_keys($attributeMappings));
        $attributeNamesInDestination = array_merge(array_keys($attributesInBoth), array_values($attributeMappings));
        
        // List existing ids
        $existingIds = $this->listIntersectingIds($componentName, $sourceDataset, $filter);
        
        // Clean existing ids
        $this->clean($componentName, $existingIds);
        
        // Copy all records that match the filter or only those that are among existingIds
        $this->sqlTemplateManager->run("postgres_helper#datasets/components/records/copy", [
                'schema'=>$this->dataset->getSchema(),
                'sourceDatasetName'=>$sourceDataset->getName(),
                'destinationDatasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'sourceAttributesAsStr'=>implode(',', $attributeNamesInSource),
                'destinationAttributesAsStr'=>implode(',', $attributeNamesInDestination),
                'filter' => $filter,
                'idsAsStr' => $existingOnly ? "'" . implode("','",$existingIds) . "'" : null
            ]);
    }
}