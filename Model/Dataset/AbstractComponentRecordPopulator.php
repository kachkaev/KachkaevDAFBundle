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
    
    protected $maxThreadCount = 0;
    protected $defaultThreadCount = 0;
    protected $supportsGUI = false;
    
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
        if (($this->schema != null && $dataset->getSchema() != $this->schema) || ($this->types !== null && array_search($dataset->getProperty('type'), $this->types) === false)) {
            throw new \LogicException(sprintf("%s only populates datasets in schema ‘%s’ and type%s ‘%s’", get_class($this), $this->schema, count($this->types) != 1 ? 's' : '', implode('’ ‘', $this->types)));
        }
        
        if ($output == null) {
            $output = new NullOutput();
        };
        
        if (!$options) {
            $options = [];
        }
        
        if (!array_key_exists('thread-count', $options) || ($this->supportsMultipleThreads() && $options['thread-count'] === 0)) {
            $options['thread-count'] = $this->defaultThreadCount;
        }
        
        $this->validateThreadCountValue($options['thread-count']);

        if (!array_key_exists('gui', $options)) {
            $options['gui'] = false;
        }
        $this->validateGuiValue($options['gui']);
                
        $this->doPopulate($dataset, $options, $output);
    }
    
    public function supportsMultipleThreads()
    {
        return $this->maxThreadCount != 0;
    }
    
    public function supportsGUI()
    {
        return $this->supportsGUI;
    }
    
    public function validateThreadCountValue($threadCount)
    {
        if (!$this->supportsMultipleThreads() && $threadCount) {
            throw new \InvalidArgumentException(sprintf("Thread count cannot be defined as %s – the populator does not support threading", $threadCount));
        }
        if ($threadCount < 0 || $threadCount > $this->maxThreadCount || (int)$threadCount != $threadCount) {
            throw new \InvalidArgumentException(sprintf("Thread count cannot be defined as %s it should be an integer between between 0 and %s", $threadCount, $this->maxThreadCount));
        }
    }
    
    public function validateGuiValue($gui)
    {
        if (!$this->supportsGUI() && $gui) {
            throw new \InvalidArgumentException(sprintf("The populator does not support gui", $gui));
        }    
    }
    
    abstract protected function doPopulate(Dataset $dataset, array $options, OutputInterface $output);
}