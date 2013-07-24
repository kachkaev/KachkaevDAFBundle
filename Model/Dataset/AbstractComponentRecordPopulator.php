<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation as DI;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 */

abstract class AbstractComponentRecordPopulator
{
    protected $types = [];
    protected $schema = '';
    
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
    
    public function populate(Dataset $dataset, array $options = null, OutputInterface $output = null)
    {
        if ($dataset->getSchema() != $this->schema || array_search($dataset->getProperty('type'), $this->types) === false) {
            throw new \LogicException(sprintf("%s only populates datasets in schema ‘%s’ and type%s ‘%s’", get_class($this), $this->schema, count($this->types) != 1 ? 's' : '', implode('’ ‘', $this->types)));
        }
        
        if ($output == null) {
            $output = new NullOutput();
        };
        
        if (!$options) {
            $options = [];
        }
        
        $this->doPopulate($dataset, $options, $output);
    }
    
    abstract protected function doPopulate(Dataset $dataset, array $options, OutputInterface $output);
}