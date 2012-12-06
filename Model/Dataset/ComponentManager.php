<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Kachkaev\PostgresHelperBundle\Model\Validator\ValidatorInterface;

use Kachkaev\PostgresHelperBundle\Model\ManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Templating\EngineInterface;

/**
 * Manages datasets

 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 */

class ComponentManager implements ManagerInterface
{
    protected $list;
    
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
        $this->datasetManager = $dataset->getDatasetManager();
        $this->sqlTemplateManager = $this->datasetManager->getSQLTemplatManager();
        $this->nameValidator = $this->datasetManager->getValidator('component_name');
    }
    
    /**
     * Actuates the list of components from the database
     */
    public function updateList()
    {
        $list = $this->sqlTemplateManager->runAndFetchAll('kernel#datasets/components/list', [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
            ], null, \PDO::FETCH_COLUMN);
        
        $this->list = $list;
    }
    
    public function listNames()
    {
        if (null === $this->list) {
            $this->updateList();
        }
        
        return $this->list;
    }
    
    public function has($componentName)
    {
        $this->nameValidator->assertValid($componentName);

        if (null === $this->list) {
            $this->updateList();
        }
        
        return array_search($componentName, $this->list) !== false;
    }
    
    /**
     * Does nothing if given component exists, throws an exception otherwise
     * @throws \LogicException if given component does not exist
     * @throws \InvalidArgumentException if the name of given component is invalid
     */
    public function assertHaving($componentName, $errorMessage)
    {
        if (!$this->has($componentName)) {
            throw new \LogicException($errorMessage);
        }
    }
    
    /**
     * Does nothing if given component does not exist, throws an exception otherwise
     * @throws \LogicException if given component exist
     * @throws \InvalidArgumentException if the name of given component is invalid
     */
    public function assertNotHaving($componentName, $errorMessage)
    {
        if ($this->has($componentName)) {
            throw new \LogicException($errorMessage);
        }
    }
    
    public function init($componentName)
    {
        $this->assertNotHaving($componentName, sprintf('Cannot initialise component %s in dataset %s.%s as it already exists', $componentName, $this->dataset->getSchema(), $this->dataset->getName()));
        
        $this->runTaskSpecificSQLTemplate($componentName, 'init', true, true);

        $this->updateList();
    }
    
    public function populate($componentName)
    {
        $this->assertHaving($componentName, sprintf('Cannot populate component %s in dataset %s.%s as it does not exist', $componentName, $this->dataset->getSchema(), $this->dataset->getName()));
        
        $this->runTaskSpecificSQLTemplate($componentName, 'populate', true, true);
    }
    
    /**
     * Despite being a part of ManagerInterface,
     * this method is not applicable to components
     * and should not be used
     * @throws \LogicException
     */
    public function rename($oldComponentName, $newComponentName)
    {
        throw new \LogicException('As components are not objects, it is not possible to extract an instance of it');
    }
    
    /**
     * Despite being a part of ManagerInterface,
     * this method is not applicable to components
     * and should not be used
     * @throws \LogicException
     */
    public function get($componentName)
    {
        throw new \LogicException('As components are not objects, it is not possible to extract an instance of it');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Kachkaev\PostgresHelperBundle\Model\ManagerInterface::delete()
     */
    public function delete($componentName)
    {
        $this->assertHaving($componentName, sprintf('Cannot delete component %s in dataset %s.%s as it does not exist', $componentName, $this->dataset->getSchema(), $this->dataset->getName()));
        
        $schema = $this->dataset->getSchema();
        $type = $this->dataset->getProperty('type');
        $deleteMode = $componentName[0] == '_' ? 'deleteView' : 'delete';
        
        $templates = [
            "$schema#$componentName/$deleteMode.$type",
            "$schema#$componentName/$deleteMode",
            "kernel#datasets/components/$deleteMode"
        ];
        
        // TODO replace with a real query that deletes component table and functions
        $this->sqlTemplateManager->run($templates, [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
            ]);
        
        $this->updateList();
    }
    
    /**
     * Resets the component: deletes all data and drops the table in the DB if it exists, then recreates it
     * @param string $componentName
     */
    public function reset($componentName)
    {
        if ($this->has($componentName)) {
            $this->delete($componentName);
        }        
        $this->init($componentName);
    }
    
    /**
     * Add a column to the component table
     */
    public function initColumn($componentName, $columnName, $columnDefinition)
    {
        $list = $this->sqlTemplateManager->run('kernel#datasets/components/columns/init', [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                'columnName'=>$columnName,
                'columnDefinition'=>$columnDefinition,
                ]);
    
        $this->list = $list;
    }
    
    /**
     * Runs a task-specific sql template (a shortcut)
     * By default only schema- and type-specific templates are considered: {schema}#{componentName}/{task}.{type}
     * 
     * @param string $componentName
     * @param string $task
     * @param bool $considerSchemaRelatedTemplate
     * @param bool $considerKernelTemplate
     */
    protected function runTaskSpecificSQLTemplate($componentName, $task, $considerSchemaRelatedTemplate = false, $considerKernelTemplate = false)
    {
        $schema = $this->dataset->getSchema();
        $type = $this->dataset->getProperty('type');
        
        $templates = ["$schema#$componentName/$task.$type"];
        
        if ($considerSchemaRelatedTemplate) {
            $templates []= "$schema#$componentName/$task";
        }
        if ($considerKernelTemplate) {
            $templates []= "$schema#$componentName/$task";
        }
        
        $this->sqlTemplateManager->run($templates, [
                'schema'=>$this->dataset->getSchema(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
                ]);
    }
}
