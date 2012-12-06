<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Performs filtering (updates of filter_xxx columns)
 *
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 * @DI\Service("postgres_helper.filter_calculator")
 */

class FilterCalculator {
    
    /**
     *  @var ContainerInterface */
    protected $container;

    /**
     *  @var SQLTemplateManager */
    protected $sqlTemplateManager;

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container"),
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->sqlTemplateManager = $container->get('postgres_helper.sql_template_manager');
    }
    
    public function calculateFilter(Dataset $dataset, $componentName, $filterName)
    {
        // TODO look for a service that does custom filtering (not sql-based)
        $schema = $dataset->getSchema();
        $type = $dataset->getProperty('type');
        
        $templates = [
                "$schema#$componentName/filtering/$filterName.$type",
                "$schema#$componentName/filtering/$filterName",
            ];
        
        $this->sqlTemplateManager->run($templates, [
                'schema'=>$dataset->getSchema(),
                'datasetName'=>$dataset->getName(),
                'componentName'=>$componentName,
                ]);
        
    }
}