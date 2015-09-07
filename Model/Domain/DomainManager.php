<?php
namespace Kachkaev\DAFBundle\Model\Domain;

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
 * @DI\Service("daf.domain_manager")
 */

class DomainManager implements ManagerInterface
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
     * @var DomainNameValidator
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
        $this->updateList();
    }

    public function updateList()
    {
        // Method is empty because listNames is not using caching
    }

    public function listNames()
    {
        $allDomains =  $this->sqlTemplateManager->runAndFetchAllAsList("daf#domains/list");
        $filteredDomains = array_diff($allDomains, $this->systemSchemas);

        return $filteredDomains;
    }

    public function has($domainName)
    {
        $domains = $this->listNames();

        return in_array($domainName, $domains);
    }

    public function init($domainName)
    {
        $this->sqlTemplateManager->run("daf#domains/init", [
                'domainName' => $domainName
            ]);
    }

    public function delete($domainName)
    {
        if (in_array($domainName, $this->systemSchemas)) {
            throw new \InvalidArgumentException("You are not allowed to delete a domain $domainName which is a system schema");
        }

        $this->sqlTemplateManager->run("daf#domains/delete", [
                'domainName' => $domainName
            ]);
    }

    public function get($domainName)
    {
        throw new \LogicException('As domains are not objects, it is not possible to extract an instance of it');
        // Method is empty because listNames is not using caching
    }

    public function rename($oldDomainName, $newDomainName)
    {
        throw new \LogicException('Domains cannot be renamed');
    }

    /**
     * Updates all domain functions
     * Function templates are stored in *Bundle/Resources/views/pgsql/domain/functions
     *
     * There are 3 categories of functions:
     * 1) Standard - created only once in the domain
     *       myfunction.pgsql.twig
     *
     * 2) Related to datasets - created as many times as many datasets there are in the domain
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
    public function updateFunctions($domainName)
    {
        $directory = $this->container->getParameter('daf.query_templates_namespace_lookups')[$domainName]['path'] . '/pgsql/domain/functions';
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
                throw new \LogicException(sprintf("Cannot determine type of function template %s", $functionName));
            }
        }

        // Delete all functions
        $this->sqlTemplateManager->run('daf#domains/delete-all-functions', [
                'domainName' => $domainName,
                ]);

        // Add standard functions (category 1)
        foreach ($functionsByCategory[0] as $function) {
            $this->sqlTemplateManager->run($domainName.'#domain/functions/'.$function);
        };

        // Get corresponding DatasetManager
        $serviceName = sprintf('daf.dataset_manager.%s', $domainName);
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
                        $this->sqlTemplateManager->run($domainName.'#domain/functions/'.$typeSpecificFunction, [
                                'datasetName'=>$datasetName,
                                ]);
                        // Apply category 2 otherwise
                    } else {
                        $this->sqlTemplateManager->run($domainName.'#domain/functions/'.$function, [
                                'datasetName'=>$datasetName,
                                ]);
                    }
                }

                // Add functions from category 3 that do not have corresponding functions in category 2
                foreach ($functionsByCategory[2] as $typeSpecificFunction) {
                    list($function, $type) = explode('.', $typeSpecificFunction);
                    if (false === array_search($function, $functionsByCategory[1]) && $datasetType == $type) {
                        $this->sqlTemplateManager->run($domainName.'#domain/functions/'.$typeSpecificFunction, [
                                'datasetName'=>$datasetName,
                            ]);
                    }
                }

                // Add functions from category 4
                foreach ($functionsByCategory[3] as $componentInstanceSpecificFunction) {
                    list($nothing1, $familyName, $nothing2, $functionName) = explode('__', $componentInstanceSpecificFunction);
                    $instanceNames = $dataset->getComponentManager()->listInstanceNames($familyName);
                    foreach($instanceNames as $instanceName) {
                        $this->sqlTemplateManager->run($domainName.'#domain/functions/'.$componentInstanceSpecificFunction, [
                                'datasetName'=>$datasetName,
                                'componentInstanceName'=>$instanceName,
                                ]);
                    }
                }
            }
        }
    }

    /**
     * Updates all domain types
     * Type templates are stored in *Bundle/Resources/views/pgsql/domain/types
     */
    public function updateTypes($domainName)
    {
        $directory = $this->container->getParameter('daf.query_templates_namespace_lookups')[$domainName]['path'] . '/pgsql/domain/functions';
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
            $this->sqlTemplateManager->run($domainName.'#domain/types/'.$templateName);
        }
    }
}