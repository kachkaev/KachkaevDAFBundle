<?php
namespace Kachkaev\DAFBundle\Model\Dataset;

use Kachkaev\DAFBundle\Model\Dataset\ComponentAttributeUpdater\AbstractComponentAttributeUpdater;
use Kachkaev\DAFBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 * Keeps all services tagged as daf.property_updater
 *
 * @DI\Service("daf.property_updaters")
 */
class PropertyUpdaterCollection
{
    protected $propertyUpdaters = [];

    public function add(AbstractComponentAttributeUpdater $propertyUpdater)
    {
        array_push($this->propertyUpdaters, $propertyUpdater);
    }

    public function getAll()
    {
        return $this->propertyUpdaters;
    }
}
