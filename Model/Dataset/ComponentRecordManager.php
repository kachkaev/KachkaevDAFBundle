<?php
namespace Kachkaev\DatasetAbstractionBundle\Model\Dataset;

use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DatasetAbstractionBundle\Model\SQLTemplateManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Kachkaev\DatasetAbstractionBundle\Model\Dataset\Dataset;
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

    const COPYMODE_ALL = 0;
    const COPYMODE_EXISTING_ONLY = 1;
    const COPYMODE_MISSING_ONLY = 2;
    
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
        
        $parsedComponentName = $this->dataset->getComponentManager()->parse($componentName);
        
        $populatorServiceNames = [];
        if ($this->dataset->getProperty('type') !== null) {
            $populatorServiceNames []= sprintf('ph.dataset_component_record_populator.%s.%s.%s', $this->dataset->getSchema(), $componentName, $this->dataset->getProperty('type'));
        };
        
        if ($parsedComponentName['instanceName']) {
            $populatorServiceNames []= sprintf('ph.dataset_component_record_populator.%s.%s__', $this->dataset->getSchema(), $parsedComponentName['familyName']);
        }
        
        $populatorServiceNames []= sprintf('ph.dataset_component_record_populator.%s.%s', $this->dataset->getSchema(), $componentName);

        foreach ($populatorServiceNames as $populatorServiceName) {
            if ($this->container->has($populatorServiceName)) {
                $populator = $this->container->get($populatorServiceName);
                break;
            }
        }
        
        if ($populator != null) {
            $populator->populate($this->dataset, $componentName, $options, $output);
        } else {
            // Populator not found. Trying to apply sql templates
            $sqlPopulator = $this->container->get('ph.dataset_component_record_populator.sql_template_based');
            $sqlPopulatorTemplateNames = $sqlPopulator->getSearchableTemplateNames($this->dataset, $componentName);
            if ($sqlPopulator->hasTemplateToExecute($this->dataset, $componentName)) {
                $sqlPopulator->populate($this->dataset, $componentName, $options);
            } else {
                throw new \LogicException(sprintf('Data populator not found. Were looking for services %s, also checked templates %s', implode(', ', $populatorServiceNames), implode(', ', $sqlPopulatorTemplateNames)));
            }
        }
    }

    /**
     * 
     * @param string $componentName
     * @param string $filter
     */
    public function count($componentName, $filter = '')
    {
        return $this->sqlTemplateManager->runAndFetchAll("dataset_abstraction#datasets/components/records/count", [
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
        return $this->sqlTemplateManager->runAndFetchAll("dataset_abstraction#datasets/components/records/count-intersecting-ids", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'dataset2Name'=>$dataset2->getName(),
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
        return $this->sqlTemplateManager->runAndFetchAllAsList("dataset_abstraction#datasets/components/records/list-intersecting-ids", [
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
            $this->sqlTemplateManager->run("dataset_abstraction#datasets/components/records/clean-by-filter", [
                    'schema'=>$this->dataset->getSchema(),
                    'datasetName'=>$this->dataset->getName(),
                    'componentName'=>$componentName,
                    'filter'=>$filterOrIds,
                    ]);
        } elseif (is_array($filterOrIds)) {
            
            $idChunks = array_chunk($filterOrIds, 1000);
            
            foreach ($idChunks as $idChunk) {
                $this->sqlTemplateManager->run("dataset_abstraction#datasets/components/records/clean-by-ids", [
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
     * @param int $copyMode COPYMODE_ALL, COPYMODE_EXISTING_ONLY, COPYMODE_MISSING_ONLY
     * @param boolean $ignoreAttributeMismatch
     * @param array $attributeMappings associative array of attribute (column) names that need to be renamed / casted, e.g. myfield=>myfield_with_new_name or myfield::int=>myfield_of_new_type 
     */
    public function copy($componentName, Dataset $sourceDataset, $filter, $copyMode, $ignoreAttributeMismatch, array $attributeMappings)
    {
        // Check if source is compatible with destination
        if ($sourceDataset->getSchema() != $this->dataset->getSchema()) {
            throw new \InvalidArgumentException(sprintf('Schema names mismatch: %s vs. %s', $this->dataset->getSchema(), $sourceDataset->getSchema()));
        }
        
        // List attributes
        // -- source
        $sourceAttributes = $this->sqlTemplateManager->runAndFetchAll("dataset_abstraction#datasets/components/attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$sourceDataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        // -- destination
        $destinationAttributes = $this->sqlTemplateManager->runAndFetchAll("dataset_abstraction#datasets/components/attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        $attributesSourceNotDestination = array_diff_assoc($sourceAttributes, $destinationAttributes);
        $attributesDestinationNotSource = array_diff_assoc($destinationAttributes, $sourceAttributes);
        $attributesInBoth = array_intersect_assoc($sourceAttributes, $destinationAttributes);
        
        // Check mappings a::type->b or a|convert(a)->b
        $realAttributeMappings = [];
        foreach ($attributeMappings as $attributeAndTypeFrom => $attributeTo) {
            $attributeFrom = $attributeAndTypeFrom;
            
            // a|convert(a)->b
            if (strpos($attributeAndTypeFrom, '|') !== false) {
                $attributeFrom = substr($attributeFrom, 0, strpos($attributeFrom, '|'));
                $conversionExpression = substr($attributeAndTypeFrom, strlen($attributeFrom) + 1);
                if (substr_count($conversionExpression, '(') != substr_count($conversionExpression, ')')) {
                    throw new \InvalidArgumentException(sprintf('Conversion expression %s is wrong: numbers of brackets mismatch.', $conversionExpression));
                }
                if (substr_count($conversionExpression, '[') != substr_count($conversionExpression, ']')) {
                    throw new \InvalidArgumentException(sprintf('Conversion expression %s is wrong: numbers of square brackets mismatch.', $conversionExpression));
                }
                $realAttributeMappings[$conversionExpression] = $attributeTo;
            
            } else {
                // a::type->b
                if (strpos($attributeFrom, ':') !== false) {
                    $attributeFrom = substr($attributeFrom, 0, strpos($attributeFrom, ':'));
                }
                $realAttributeMappings[$attributeAndTypeFrom] = $attributeTo;
            }
            
            if ($attributeFrom && !array_key_exists($attributeFrom, $sourceAttributes)) {
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
        
        $attributeNamesInSource = array_unique(array_merge(array_keys($attributesInBoth), array_keys($realAttributeMappings)));
        $attributeNamesInDestination = array_unique(array_merge(array_keys($attributesInBoth), array_values($realAttributeMappings)));
        
        // List existing ids
        $existingIds = $this->listIntersectingIds($componentName, $sourceDataset, $filter);
        
        // Clean existing ids
        if ($copyMode != self::COPYMODE_MISSING_ONLY) {
            $this->clean($componentName, $existingIds);
        }
        
        // Copy all records that match the filter or only those that are among existingIds
        echo $this->sqlTemplateManager->run("dataset_abstraction#datasets/components/records/copy", [
                'schema'=>$this->dataset->getSchema(),
                'sourceDatasetName'=>$sourceDataset->getName(),
                'destinationDatasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'sourceAttributesAsStr'=>implode(',', $attributeNamesInSource),
                'destinationAttributesAsStr'=>implode(',', $attributeNamesInDestination),
                'filter' => $filter,
                'idsAsStr' => ($copyMode != self::COPYMODE_ALL) ? "'" . implode("','",$existingIds) . "'" : null,
                'missingOnly' => $copyMode == self::COPYMODE_MISSING_ONLY
            ]);
    }
}