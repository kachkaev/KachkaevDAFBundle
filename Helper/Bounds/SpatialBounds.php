<?php
namespace Kachkaev\DAFBundle\Helper\Bounds;

use Kachkaev\DAFBundle\Helper\Bounds\AbstractBounds;

class SpatialBounds extends AbstractBounds
{
    /**
     *  @var \Geometry */
    protected $geometry;

    function __construct ($rawPropertyValue)
    {
        parent::__construct($rawPropertyValue);
        $this->geometry = \geoPHP::load($rawPropertyValue);
    }

    public function generateTestSQL($columnName)
    {
        return sprintf(
            'ST_Contains(ST_SetSRID(ST_GeomFromText(\'%s\'), 4326), ST_SetSRID(%s::geometry, 4326))'
            , $this->rawPropertyValue
            , $columnName
        );
    }
}