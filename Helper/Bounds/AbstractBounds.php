<?php
namespace Kachkaev\DAFBundle\Helper\Bounds;

abstract class AbstractBounds
{
    protected $rawPropertyValue;

    function __construct ($rawPropertyValue)
    {
        $this->rawPropertyValue = $rawPropertyValue;
    }

    abstract public function generateTestSQL($columnName);
}