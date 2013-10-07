<?php
namespace Kachkaev\PostgresHelperBundle\Model;

use Doctrine\DBAL\Portability\Connection;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\DBAL\Driver\PDOStatement;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Templating\EngineInterface;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("postgres_helper.sql_template_manager")
 */

class SQLTemplateManager
{
    /**
     *  @var ContainerInterface */
    protected $container;

    /**
     *  @var DriverConnection */
    private $connection;

    /**
     *  @var EngineInterface */
    protected $templating;

    protected $queryTemplatesNamespaceLookup;
    
    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container"),
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->templating = $container->get('templating');
        $this->queryTemplatesNamespaceLookup = $container->getParameter('postgres_helper.query_templates_namespace_lookups');
    }
    
    protected function initializeConnectionIfNeeded()
    {
        if ($this->connection)
            return;
        
        $this->connection = $this->container->get("postgres_helper.real_db_connection.main");
    }
    
    /**
     * Checks whether the given templates exists
     * @param string $queryTemplates
     * @return boolean
     */
    public function exists($queryTemplate)
    {
        $queryTemplatePath = $this->getQueryTemplatePath($queryTemplate);
        return $this->templating->exists($queryTemplatePath);
    }
    
    /**
     * Checks whether at least of given sql templates exist
     * @param array $queryTemplates
     * @return boolean
     */
    public function existSomeOf(array $queryTemplates)
    {
        foreach ($queryTemplates as $queryTemplate) {
            $queryTemplatePath = $this->getQueryTemplatePath($queryTemplate);
            if ($this->templating->exists($queryTemplatePath)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Checks whether all of given sql templates exists
     * @param array $queryTemplates
     * @return boolean
     */
    public function existAllOf(array $queryTemplates)
    {
        foreach ($queryTemplates as $queryTemplate) {
            $queryTemplatePath = $this->getQueryTemplatePath($queryTemplate);
            if (!$this->templating->exists($queryTemplatePath)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Renders the first template found in the list of $queryTemplates
     * @param array|string $queryTemplates one or several query templates to look for
     * @param array $templateParams
     * @return string
     * @throws \InvalidArgumentException if none of given queries are found
     */
    
    public function render($queryTemplates, $templateParams = [])
    {
        if (is_string($queryTemplates)) {
            $queryTemplates = [$queryTemplates];
        }
        if ($templateParams === null) {
            $templateParams = [];
        }

        foreach ($queryTemplates as $queryTemplate) {
            $queryTemplatePath = $this->getQueryTemplatePath($queryTemplate);
            if ($this->templating->exists($queryTemplatePath)) {
                return $this->templating->render($queryTemplatePath, $templateParams);
            }
        }
        
        if (sizeof($queryTemplates) == 1) {
            throw new \InvalidArgumentException(sprintf('Query template %s was not found', $queryTemplates[0]));
        } else {
            throw new \InvalidArgumentException(sprintf('None of the following query templates were found: %s', implode(', ', $queryTemplates)));
        }
        
    }

    public function prepare($queryTemplates, $templateParams = [])
    {
        $this->initializeConnectionIfNeeded();
    
        $query = $this->render($queryTemplates, $templateParams);
    
        return $this->connection->prepare($query);
    }
    
    public function run($queryTemplates, $templateParams = [], $queryParams = null)
    {
        $this->initializeConnectionIfNeeded();
        
        $query = $this->render($queryTemplates, $templateParams);
        
        // Prepare and run if there are any $queryParams, just execute otherwise
        if ($queryParams) {
            $statement = $this->connection->prepare($query);
            $statement->execute($queryParams);
        } else {
            $this->connection->exec($query);
        }
    }

    public function runAndReturnStatement($queryTemplates, $templateParams = [], $queryParams = null)
    {
        $this->initializeConnectionIfNeeded();
        
        $query = $this->render($queryTemplates, $templateParams);
        $statement = $this->connection->prepare($query);
        $statement->execute($queryParams);
        
        return $statement;
    }
    
    public function runAndFetchAll($queryTemplates, $templateParams = [], $queryParams = null, $fetchStyle = null, $fetchColumn = null)
    {
        $this->initializeConnectionIfNeeded();
        
        $query = $this->render($queryTemplates, $templateParams);
        $statement = $this->connection->prepare($query);
        $statement->execute($queryParams);

        if (null === $fetchStyle) {
            $fetchStyle = \PDO::FETCH_ASSOC;
        }
            
        
        if ($fetchColumn) {
            $result = $statement->fetchAll($fetchStyle, $fetchColumn);
        } else {
            $result = $statement->fetchAll($fetchStyle);
        }
        return $result;
    }
    
    public function runAndSaveToFile($pathToFile, $queryTemplates, $templateParams = [], $queryParams = null)
    {
        $this->initializeConnectionIfNeeded();
        
        $query = sprintf('COPY (%s) TO \'%s\' WITH CSV HEADER', $this->render($queryTemplate, $templateParams), $pathToFile);
        $statement = $this->connection->prepare($query);
        $statement->execute();
    }
    
    public function runAndFetchAllAsList($queryTemplates, $templateParams = [], $queryParams = null, $fetchStyle = null, $fetchColumn = null)
    {
        return $this->runAndFetchAll($queryTemplates, $templateParams, $queryParams, \PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * Returns currently used Connection
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public function getConnection()
    {
        $this->initializeConnectionIfNeeded();
        
        return $this->connection;
    }   

    /**
     * Returns currently used template engine
     * @return \Symfony\Component\Templating\EngineInterface
     */
    public function getTemplating()
    {
        return $this->templating;
    }
    
    
    /**
     * Converts short address of sql template to twig-compatable format
     * 
     * Example:
     *     postgres_helper#a/b/c → KachkaevPostgresHelperBundle:sql/a/b:c.pgsql.twig 
     */
    protected function getQueryTemplatePath($queryTemplate)
    {
        if (!is_string($queryTemplate) && !$queryTemplate)
            throw new \InvalidArgumentException(sprintf('Template name must be a non-empty string, got %s', var_export($queryTemplate, true)));
        
        $queryTemplateParts = explode("#", $queryTemplate);
        if (array_key_exists(1, $queryTemplateParts))
        $queryTemplateParts[1] = 'sql/'.$queryTemplateParts[1];
        
        if ($queryTemplateParts[0] == 'postgres_helper') {
            $queryBundle = 'KachkaevPostgresHelperBundle';
        } else {
            if (!array_key_exists($queryTemplateParts[0], $this->queryTemplatesNamespaceLookup)) {
                throw new \InvalidArgumentException(sprintf('Don’t know where to search for templates starting with ‘%s’', $queryTemplateParts[0]));
            } else {
                $queryBundle = $this->queryTemplatesNamespaceLookup[$queryTemplateParts[0]]['bundle'];
            }
        }
        $result =  $queryBundle.':'.str_lreplace('/', ':', $queryTemplateParts[1]).'.pgsql.twig';
        
        return $result;
    }
    
    public function getTemplateNamespacePath($schemaName) {
        if (!array_key_exists($schemaName, $this->queryTemplatesNamespaceLookup)) {
            throw new \InvalidArgumentException(sprintf('SQL template namespace %s not found, please add it to postgres_helper.query_templates_namespace_lookups parameter', $schemaName));
        }
        return $this->queryTemplatesNamespaceLookup[$schemaName]['path'];
    }
}

// See http://stackoverflow.com/questions/3835636/php-replace-last-occurence-of-a-string-in-a-string
function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);

    if($pos !== false)
    {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;
}