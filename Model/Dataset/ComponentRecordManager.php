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
        return $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-records/count", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'filter'=>$filter,
                ])[0]['count'];
    }
    
    /**
     * Returns ids of records that both exist in the same component of $dataset2 and the current dataset
     * 
     * @param string $componentName
     * @param Dataset $dataset2
     * @param string $filter
     */
    public function getIntersectingIds($componentName, Dataset $dataset2, $filter)
    {
        return $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-records/count", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'filter'=>$filter,
                ])[0]['count'];
    }
    
    /**
     * 
     * @param string $componentName
     * @param string $filter
     */
    public function clean($componentName, $filter)
    {
        $this->sqlTemplateManager->run("postgres_helper#datasets/component-records/clean", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'filter'=>$filter,
                ]);
    }
    
    /**
     * Copies records from the given dataset component to the current dataset
     *  
     * @param string $componentName
     * @param Dataset $sourceDataset
     * @param string $filter
     * @param boolean $existingOnly
     * @param boolean $ignoreStructureDifference
     * @param array $attributeMappings associative array of attribute (column) names that need to be renamed / casted, e.g. myfield=>myfield_with_new_name or myfield::int=>myfield_of_new_type 
     * @param OutputInterface $output
     */
    public function copy($componentName, Dataset $sourceDataset, $filter, $existingOnly, $ignoreStructureDifference, array $attributeMappings, OutputInterface $output = null)
    {
        // Check if source is compatible with destination
        if ($sourceDataset->getSchema() != $this->dataset->getSchema()) {
            throw new \InvalidArgumentException(sprintf('Schema names mismatch: %s vs. %s', $this->dataset->getSchema(), $sourceDataset->getSchema()));
        }
        
        // List attributes
        // -- source
        $sourceAttributes = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$sourceDataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        // -- destination
        $destinationAttributes = $this->sqlTemplateManager->runAndFetchAll("postgres_helper#datasets/component-attributes/list", [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                ], null, \PDO::FETCH_KEY_PAIR);
        
        
        var_dump(array_diff_assoc($sourceAttributes, $destinationAttributes));
        var_dump(array_diff_assoc($destinationAttributes, $sourceAttributes));
        
        //var_dump($sourceAttributes, $destinationAttributes);
        // select records to copy 
        
    }
}