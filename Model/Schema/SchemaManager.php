<?php
namespace Kachkaev\DAFBundle\Model\Schema;

use Symfony\Component\Finder\Finder;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\DBAL\Statement;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Templating\EngineInterface;

use Kachkaev\DAFBundle\Model\ManagerInterface;
use Kachkaev\DAFBundle\Model\TemplateManaging\SQLTemplateManager;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("daf.schema_manager")
 */

class SchemaManager implements ManagerInterface
{

    protected $systemSchemas = [
            "pg_toast",
            "pg_temp_1",
            "pg_toast_temp_1",
            "pg_catalog",
            "information_schema",
        ];

    /**
     *  @var ContainerInterface */
    protected $container;

    /**
     *  @var SQLTemplateManager */
    protected $sqlTemplateManager;

    /**
     * @var SchemaNameValidator
     */
    protected $nameValidator;

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container"),
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->sqlTemplateManager = $container->get('daf.sql_template_manager');
        //$this->nameValidator = $this->getValidator('schema_name');
        $this->updateList();
    }

    public function updateList()
    {
        // Method is empty because listNames is not using caching
    }

    public function listNames()
    {
        $allSchemas =  $this->sqlTemplateManager->runAndFetchAllAsList("dataset_abstraction#schemas/list");
        $filteredSchemas = array_diff($allSchemas, $this->systemSchemas);

        return $filteredSchemas;
    }

    public function has($schemaName)
    {
        $schemas = $this->listNames();

        return in_array($schemaName, $schemas);
    }

    public function init($schemaName)
    {
        $this->sqlTemplateManager->run("dataset_abstraction#schemas/init", [
                'schema' => $schemaName
            ]);
    }

    public function delete($schemaName)
    {
        if (in_array($schemaName, $this->systemSchemas)) {
            throw new \InvalidArgumentException("You are not allowed to delete system schema $schemaName");
        }

        $this->sqlTemplateManager->run("dataset_abstraction#schemas/delete", [
                'schema' => $schemaName
            ]);
    }

    public function get($schemaName)
    {
        throw new \LogicException('As schemas are not objects, it is not possible to extract an instance of it');
        // Method is empty because listNames is not using caching
    }

    public function rename($oldSchemaName, $newSchemaName)
    {
        throw new \LogicException('Schemas cannot be renamed');
    }

    /**
     * Updates all schema functions
     * Function templates are stored in *Bundle/Resources/views/pgsql/schema/functions
     *
     * There are 3 categories of functions:
     * 1) Standard - created only once in the schema
     *       myfunction.pgsql.twig
     *
     * 2) Related to datasets - created as many times as many datasets there are in the schema
     *       dataset__{my_function}.pgsql.twig
     *
     * 3) Related to datasets and dataset types - same as 2, but is applicable only to datasets having a certain type
     *       dataset__{my_function}.{dataset_type}.pgsql.twig
     *
     * 4) Related to datasets and specific to instances of components (for components with multiple instances)
     *      dataset__{component_family_name}__instance__{my_function}.pgsql.twig
     *
     * 5) [stub] Related to datasets, specific to instances of components and type
     *      dataset__{component_family_name}__instance__{my_function}.{dataset_type}.pgsql.twig
     *
     */
    public function updateFunctions($schemaName)
    {
        $directory = $this->container->getParameter('daf.query_templates_namespace_lookups')[$schemaName]['path'] . '/pgsql/schema/functions';
        if (!is_dir($directory)) {
            return;
        }

        // Find all pgsql templates in the directory
        $finder = new Finder();
        $finder
        ->files()
        ->in($directory)
        ->name('*.pgsql.twig')
        ->depth(0);

        $functionsByCategory = [[], [], [], []];

        // Categorise found templates
        foreach ($finder as $file) {
            $functionName = $file->getBaseName('.pgsql.twig');
            preg_match('/^(dataset__)?([^\.]*?)((__instance__([^\.]+))?)((\.(.+))?)$/', $functionName, $matches);
            if (!$matches[1]) {
                $functionsByCategory[0] []= $functionName;
            } else if (!$matches[6] && !$matches[3]) { // Not specific to type and component instance
                $functionsByCategory[1] []= $functionName;
            } else if ($matches[6] && !$matches[3]) { // Specific to type, but not to component instance
                $functionsByCategory[2] []= $functionName;
            } else if (!$matches[6] && $matches[3]) { // Specific component instance, but not to type
                $functionsByCategory[3] []= $functionName;
            } else {
                throw new \LogicExcepton(sprintf("Cannot determine type of function template %s", $functionName));
            }
        }

        // Delete all functions
        $this->sqlTemplateManager->run('dataset_abstraction#schemas/delete-all-functions', [
                'schema' => $schemaName,
                ]);

        // Add standard functions (category 1)
        foreach ($functionsByCategory[0] as $function) {
            $this->sqlTemplateManager->run($schemaName.'#schema/functions/'.$function);
        };

        // Get corresponding DatasetManager
        $serviceName = sprintf('daf.dataset_manager.%s', $schemaName);
        if ($this->container->has($serviceName)) {
            $datasetManager = $this->container->get($serviceName);

            // Add dataset-related functions (cateogries 2 and 3)
            foreach ($datasetManager->listNames() as $datasetName) {
                $dataset = $datasetManager->get($datasetName);
                $datasetType = $dataset->getProperty('type');

                // Loop through functions of cateogry 2 and apply them or add corresponding functions from category 3
                foreach ($functionsByCategory[1] as $function) {
                    $typeSpecificFunction = $function.'.'.$datasetType;
                    // Apply category 3 if type-specific function exists
                    if (false !== array_search($typeSpecificFunction, $functionsByCategory[2])) {
                        $this->sqlTemplateManager->run($schemaName.'#schema/functions/'.$typeSpecificFunction, [
                                'datasetName'=>$datasetName,
                                ]);
                        // Apply category 2 otherwise
                    } else {
                        $this->sqlTemplateManager->run($schemaName.'#schema/functions/'.$function, [
                                'datasetName'=>$datasetName,
                                ]);
                    }
                }

                // Add functions from category 3 that do not have corresponding functions in category 2
                foreach ($functionsByCategory[2] as $typeSpecificFunction) {
                    list($function, $type) = explode('.', $typeSpecificFunction);
                    if (false === array_search($function, $functionsByCategory[1]) && $datasetType == $type) {
                        $this->sqlTemplateManager->run($schemaName.'#schema/functions/'.$typeSpecificFunction, [
                                'datasetName'=>$datasetName,
                            ]);
                    }
                }

                // Add functions from category 4
                foreach ($functionsByCategory[3] as $componentInstanceSpecificFunction) {
                    list($nothing1, $familyName, $nothing2, $functionName) = explode('__', $componentInstanceSpecificFunction);
                    $instanceNames = $dataset->getComponentManager()->listInstanceNames($familyName);
                    foreach($instanceNames as $instanceName) {
                        $this->sqlTemplateManager->run($schemaName.'#schema/functions/'.$componentInstanceSpecificFunction, [
                                'datasetName'=>$datasetName,
                                'componentInstanceName'=>$instanceName,
                                ]);
                    }
                }
            }
        }
    }

    /**
     * Updates all schema types
     * Type templates are stored in *Bundle/Resources/views/pgsql/schema/types
     */
    public function updateTypes($schemaName)
    {
        $directory = $this->container->getParameter('daf.query_templates_namespace_lookups')[$schemaName]['path'] . '/pgsql/schema/functions';
        if (!is_dir($directory)) {
            return;
        }

        // Find all pgsql templates in the directory
        $finder = new Finder();
        $finder
        ->files()
        ->in($directory)
        ->name('*.pgsql.twig')
        ->depth(0);

        // Execute all templates
        foreach ($finder as $file) {
            $templateName = $file->getBaseName('.pgsql.twig');
            $this->sqlTemplateManager->run($schemaName.'#schema/types/'.$templateName);
        }
    }
}