<?php
namespace Kachkaev\DAFBundle\Helper\Bounds;

use Kachkaev\DAFBundle\Helper\Bounds\AbstractBounds;

class TemporalBounds extends AbstractBounds
{
    protected $minTimeAsString;
    protected $maxTimeAsString;

    function __construct ($rawPropertyValue)
    {
        parent::__construct($rawPropertyValue);
        $parts = explode('...', $rawPropertyValue);
        $this->minTimeAsString = $parts[0];
        $this->maxTimeAsString = $parts[1];
    }

    public function generateTestSQL($columnName)
    {
        return sprintf('("%s" >= \'%s\' AND "%s" <= \'%s\')', $columnName, $this->getMinTimeAsString(), $columnName, $this->getMaxTimeAsString());
    }

    public function getMinTimeAsString($format = null)
    {
        return $this->minTimeAsString;
    }
    public function getMaxTimeAsString($format = null)
    {
        return $this->maxTimeAsString;
    }
}