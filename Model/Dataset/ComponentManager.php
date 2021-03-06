<?php
namespace Kachkaev\DAFBundle\Model\Dataset;

use Kachkaev\DAFBundle\Model\Validator\ValidatorInterface;

use Kachkaev\DAFBundle\Model\ManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Kachkaev\DAFBundle\Model\TemplateManaging\SQLTemplateManager;
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
        $list = $this->sqlTemplateManager->runAndFetchAll('daf#datasets/components/list', [
                'domainName'=>$this->dataset->getDomainName(),
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

        return array_search($componentName, $this->list, true) !== false;
    }

    /**
     * Does nothing if given component exists, throws an exception otherwise
     * @throws \LogicException if given component does not exist
     * @throws \InvalidArgumentException if the name of given component is invalid
     */
    public function assertHaving($componentName, $errorMessage = null)
    {
        if (!$errorMessage) {
            $errorMessage = sprintf('Component %s in dataset %s does not exist', $componentName, $this->dataset->getFullName());
        }

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
        $this->assertNotHaving($componentName, sprintf('Cannot initialise component %s in dataset %s.%s as it already exists', $componentName, $this->dataset->getDomainName(), $this->dataset->getName()));

        $this->runTaskSpecificSQLTemplate($componentName, 'init', true, true);

        $this->updateList();
        $this->datasetManager->updateFunctions();

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
     * @see \Kachkaev\DAFBundle\Model\ManagerInterface::delete()
     */
    public function delete($componentName)
    {
        $this->assertHaving($componentName, sprintf('Cannot delete component %s in dataset %s.%s as it does not exist', $componentName, $this->dataset->getDomainName(), $this->dataset->getName()));

        $domain = $this->dataset->getDomainName();
        $type = $this->dataset->getProperty('type');
        $deleteMode = $componentName[0] == '_' ? 'deleteView' : 'delete';

        $templates = [
            "$domain#components/$componentName/$deleteMode.$type",
            "$domain#components/$componentName/$deleteMode",
            "daf#datasets/components/$deleteMode"
        ];

        // TODO replace with a real query that deletes component table and functions
        $this->sqlTemplateManager->run($templates, [
                'domainName'=>$this->dataset->getDomainName(),
                'datasetName'=>$this->dataset->getName(),
                'componentName'=>$componentName,
            ]);

        $this->updateList();
        $this->datasetManager->updateFunctions();

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

    public function count($componentName) {
        return $this->dataset->getComponentRecordManager()->count($componentName);
    }

    public function countUnprocessed($componentName) {
        return $this->dataset->getComponentRecordManager()->count($componentName, 'status <> 3 AND status <> 2');
    }

    /**
     * Runs a task-specific sql template (a shortcut)
     * By default only domain- and type-specific templates are considered: {domainName}#components/{componentName}/{task}.{type}
     *
     * @param string $componentName
     * @param string $task
     * @param bool $considerDomainRelatedTemplate
     * @param bool $considerDatasetAbstractionTemplate
     */
    protected function runTaskSpecificSQLTemplate($componentName, $task, $considerDomainRelatedTemplate = false, $considerDatasetAbstractionTemplate = false)
    {
        $domain = $this->dataset->getDomainName();
        $type = $this->dataset->getProperty('type');

        $parsedComponentName = $this->parse($componentName);

        $templates = [sprintf('%s#components/%s/%s.%s', $domain, $componentName, $task, $type)];

        if ($parsedComponentName['instanceName']) {
            $templates []= sprintf('%s#components/%s__/%s.%s', $domain, $parsedComponentName['familyName'], $task, $type);
        }

        if ($considerDomainRelatedTemplate) {
            $templates []= sprintf('%s#components/%s/%s', $domain, $componentName, $task);
            if ($parsedComponentName['familyName']) {
                $templates []= sprintf('%s#components/%s__/%s', $domain, $parsedComponentName['familyName'], $task);
            }
        }

        $this->sqlTemplateManager->run($templates, [
                'domainName' => $this->dataset->getDomainName(),
                'datasetName' => $this->dataset->getName(),
                'componentName' => $componentName,
                'componentInstanceName' => $parsedComponentName['instanceName'],
            ]);
    }

    /**
     * Some components may have multiple instances. A sign for a multi-instance component (component family)
     * is a double underscore in the component name.
     * E.g.
     * my_dataset -> my_component__one
     * my_dataset -> my_component__two
     * my_dataset -> my_component__three
     *
     * my_dataset -> my_component__something__something_else
     *
     * This method extracts component family and component instance and returns values in array
     *
     * @param string $componentName
     * @return array
     *         for a simple component:
     *              ['familyName' => null, 'instanceName' => null, 'instanceParts' => null]
     *         for multi-instance components:
     *              ['familyName' => 'my_component', 'instanceName' => 'one', 'instanceParts' => ['one']]
     *              ['familyName' => 'my_component', 'instanceName' => 'something__something_else', 'instanceParts' => ['something', 'something_else']]
     */
    public function parse($componentName)
    {
        $componentParts = explode('__', $componentName);
        if (count($componentParts) > 1) {
            $result = [
                'familyName' => $componentParts[0],
                'instanceName' => substr($componentName, strlen($componentParts[0]) + 2)
            ];
            array_shift($componentParts);
            $result['instanceParts'] = $componentParts;
        } else {
            $result = [
                'familyName' => null,
                'instanceName' => null,
                'instanceParts' => null
            ];
        }

        return $result;
    }

    /**
     * Returns names of instances for components of a defined family name.
     * E.g. dataset consists of
     *     component1
     *     component2
     *     component3__isntance1
     *     component3__isntance2
     *     component3__isntance3
     *
     * then
     * listInstanceNames('component3') → [instance1, instance2, instance3]
     * listInstanceNames('component2') → []
     * listInstanceNames('component42') → []
     *
     * @param string $familyName
     */
    public function listInstanceNames($familyName)
    {
        if (!$familyName && !is_string($familyName)) {
            throw new \InvalidArgumentException(sprintf("Argument familyName must be alphanumeric, %s given.", var_export($familyName, true)));
        }
        $list = $this->listNames();
        $result = [];
        foreach ($list as $componentName) {
            $parsedComponentName = $this->parse($componentName);
            if ($familyName === $parsedComponentName['familyName']) {
                $result []= $parsedComponentName['instanceName'];
            }
        }

        return $result;
    }
}
