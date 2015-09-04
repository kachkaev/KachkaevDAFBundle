<?php
namespace Kachkaev\DAFBundle\Model\TemplateManaging;

use Doctrine\DBAL\Portability\Connection;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("daf.sql_template_manager")
 */

class SQLTemplateManager extends AbstractTemplateManager
{
    /**
     *  @var DriverConnection */
    private $connection;

    protected $templateType = 'pgsql'; // Dir name inside Resources/views and template extension

    protected function initializeConnectionIfNeeded()
    {
        if ($this->connection)
            return;

        $this->connection = $this->container->get("daf.real_db_connection.main");
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
}
